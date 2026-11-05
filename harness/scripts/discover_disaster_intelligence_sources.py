#!/usr/bin/env python3

import argparse
import json
import os
import sys
from datetime import datetime
from pathlib import Path
from urllib.parse import urlparse

SCRIPT_DIR = Path(__file__).resolve().parent
sys.path.insert(0, str(SCRIPT_DIR))

from refresh_disaster_links import (  # noqa: E402
    DEFAULT_MODEL,
    DEFAULT_LOG,
    REPO,
    acquire_lock,
    extract_json_object,
    fetch_context,
    log,
    release_lock,
    run_command,
    run_opencode,
    url_is_available,
)

TEMP_DIR = REPO / 'storage/app/disaster-intelligence'
CONTAINER_ROOT = '/var/www/html'
PROMPT_PATH = REPO / 'resources/prompts/discover_sources.txt'
LOCK_FILE = Path('/tmp/saiz-discover-intelligence-sources.lock')
ALLOWED_TYPES = {'roads', 'waste_sites', 'meeting_insights'}


def fetch_source_context(disaster_id):
    result = run_command(
        ['docker', 'compose', 'exec', '-T', 'app', 'php', 'artisan', 'disaster-intelligence:source-context', str(disaster_id)],
        cwd=REPO,
        timeout=120,
    )

    if result.returncode != 0:
        raise RuntimeError('収集元コンテキストの取得に失敗しました: ' + result.stderr.strip())

    return json.loads(result.stdout)


def build_source_prompt(context):
    template = PROMPT_PATH.read_text(encoding='utf-8')
    existing = context.get('sources') or []
    existing_urls = '\n'.join(
        f"- {source.get('type')}: {source.get('url')}"
        for source in existing
        if source.get('url')
    ) or '（なし）'

    replacements = {
        '{{DISASTER_NAME}}': str(context.get('name') or ''),
        '{{EVENT_DATE}}': str(context.get('event_date') or ''),
        '{{MUNICIPALITIES}}': '、'.join(context.get('municipalities') or []),
        '{{EXISTING_URLS}}': existing_urls,
    }

    for key, value in replacements.items():
        template = template.replace(key, value)

    return template


def normalize_rank(value):
    value = str(value or 'C').strip().upper()

    return value if value in ('A', 'B', 'C') else 'C'


def domain_rank(url):
    host = (urlparse(url).hostname or '').lower()
    host = host.removeprefix('www.')

    if host.endswith('.go.jp') or host.endswith('.lg.jp'):
        return 'A'

    if host.startswith(('city.', 'town.', 'vill.', 'pref.')) and host.endswith('.jp'):
        return 'A'

    if host.endswith('.or.jp') or host in ('jvoad.jp', 'kvoad.com'):
        return 'B'

    return 'C'


def worse_rank(left, right):
    order = {'A': 1, 'B': 2, 'C': 3}

    return left if order[left] >= order[right] else right


def normalize_sources(items, verify_urls):
    if isinstance(items, dict):
        items = items.get('sources') or []

    if not isinstance(items, list):
        return []

    normalized = []
    seen = set()

    for item in items:
        if not isinstance(item, dict):
            continue

        source_type = str(item.get('type') or '').strip()

        if source_type not in ALLOWED_TYPES:
            continue

        url = str(item.get('url') or '').strip()

        if not url.startswith(('http://', 'https://')):
            continue

        key = (source_type, url)

        if key in seen:
            continue

        source_org = str(item.get('source_org') or '').strip()

        if not source_org:
            continue

        if verify_urls and not url_is_available(url):
            continue

        seen.add(key)
        normalized.append({
            'type': source_type,
            'url': url,
            'source_org': source_org,
            'title': str(item.get('title') or '').strip(),
            'published_at': item.get('published_at'),
            'credibility_rank': worse_rank(
                normalize_rank(item.get('credibility_rank')),
                domain_rank(url),
            ),
            'credibility_reason': str(item.get('credibility_reason') or '').strip(),
        })

    return normalized


def ingest_sources(disaster_id, sources):
    TEMP_DIR.mkdir(parents=True, exist_ok=True)
    path = TEMP_DIR / f'sources-{disaster_id}-{datetime.now().strftime("%Y%m%d%H%M%S")}.json'

    try:
        path.write_text(
            json.dumps({'sources': sources}, ensure_ascii=False, indent=2),
            encoding='utf-8',
        )
        result = run_command(
            [
                'docker', 'compose', 'exec', '-T', 'app', 'php', 'artisan',
                'disaster-intelligence:source-ingest', str(disaster_id), '--file=' + CONTAINER_ROOT + '/' + str(path.relative_to(REPO)),
            ],
            cwd=REPO,
            timeout=120,
        )

        if result.returncode != 0:
            raise RuntimeError(result.stderr.strip() or result.stdout.strip())
    finally:
        path.unlink(missing_ok=True)


def sync_sources(disaster_id):
    result = run_command(
        ['docker', 'compose', 'exec', '-T', 'app', 'php', 'artisan', 'disaster-intelligence:sync', str(disaster_id)],
        cwd=REPO,
        timeout=600,
    )

    if result.returncode != 0:
        raise RuntimeError(result.stderr.strip() or result.stdout.strip())

    return result.stdout.strip()


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument('--disaster-id', type=int, help='指定した災害IDのみ探索する')
    parser.add_argument('--dry-run', action='store_true')
    parser.add_argument('--model', default=os.environ.get('OPENCODE_MODEL', DEFAULT_MODEL))
    parser.add_argument('--log', default=str(DEFAULT_LOG))
    parser.add_argument('--no-url-check', action='store_true')
    parser.add_argument('--skip-sync', action='store_true')
    args = parser.parse_args()

    log_path = Path(args.log)

    if not acquire_lock(LOCK_FILE):
        log('前回の収集元探索が実行中のためスキップしました。', log_path)
        return 0

    try:
        disasters = fetch_context()

        if args.disaster_id:
            disasters = [
                disaster for disaster in disasters
                if int(disaster.get('id', 0)) == args.disaster_id
            ]

        log(f'収集元探索を開始しました（対象災害: {len(disasters)}件）。', log_path)

        for disaster in disasters:
            disaster_id = int(disaster.get('id', 0))

            if not disaster_id:
                continue

            log(f'災害ID {disaster_id}「{disaster.get("name")}」の収集元探索を開始しました。', log_path)

            try:
                context = fetch_source_context(disaster_id)
                prompt = build_source_prompt(context)
                answer = run_opencode(prompt, args.model)
                items = extract_json_object(answer)
                sources = normalize_sources(items, not args.no_url_check)
            except Exception as error:
                log(f'災害ID {disaster_id}: {error}', log_path)
                continue

            if not sources:
                log(f'災害ID {disaster_id}: 有効な収集元がありませんでした。', log_path)
                continue

            counts = {source_type: 0 for source_type in ALLOWED_TYPES}
            for source in sources:
                counts[source['type']] += 1

            summary = ' / '.join(f'{key}={value}' for key, value in counts.items() if value)
            log(f'災害ID {disaster_id}: {len(sources)} 件の収集元を検出（{summary}）。', log_path)

            if args.dry_run:
                log(f'災害ID {disaster_id}: DryRunのため登録・同期なし。', log_path)
                continue

            try:
                ingest_sources(disaster_id, sources)
                log(f'災害ID {disaster_id}: 収集元を登録しました。', log_path)
            except Exception as error:
                log(f'災害ID {disaster_id}: 収集元登録でエラー: {error}', log_path)
                continue

            if args.skip_sync:
                continue

            try:
                output = sync_sources(disaster_id)
                log(f'災害ID {disaster_id}: 同期を実行しました。{output}', log_path)
            except Exception as error:
                log(f'災害ID {disaster_id}: 同期でエラー: {error}', log_path)

        log('収集元探索を終了しました。', log_path)

        return 0
    finally:
        release_lock(LOCK_FILE)


if __name__ == '__main__':
    raise SystemExit(main())
