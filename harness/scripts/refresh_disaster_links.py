#!/usr/bin/env python3

import argparse
import html
import json
import os
import re
import subprocess
import sys
import time
import urllib.error
import urllib.request
from datetime import datetime
from pathlib import Path
from urllib.parse import urljoin

REPO = Path('/home/saiz/work/saiz')
OPENCODE = Path('/home/saiz/.opencode/bin/opencode')
DEFAULT_MODEL = 'opencode-go/deepseek-v4.1-flash'
DEFAULT_LOG = REPO / 'storage/logs/disaster-links-refresh.log'
LOCK_FILE = Path('/tmp/saiz-disaster-links-refresh.lock')
TEMP_DIR = REPO / 'storage/app/disaster-links'
VOLUNTEER_TEMP_DIR = REPO / 'storage/app/volunteer-centers'
SHELTER_STATUS_TEMP_DIR = REPO / 'storage/app/shelter-status'


def log(message, log_path):
    timestamp = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
    line = f'[{timestamp}] {message}\n'
    log_path.parent.mkdir(parents=True, exist_ok=True)
    with log_path.open('a', encoding='utf-8') as handle:
        handle.write(line)


def command_env():
    env = os.environ.copy()
    env['HOME'] = '/home/saiz'
    env['PATH'] = '/home/saiz/.opencode/bin:/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin'
    return env


def run_command(args, cwd, timeout, env=None):
    return subprocess.run(
        args,
        cwd=str(cwd),
        env=env or command_env(),
        capture_output=True,
        text=True,
        encoding='utf-8',
        timeout=timeout,
    )


def fetch_context():
    result = run_command(
        ['docker', 'compose', 'exec', '-T', 'app', 'php', 'artisan', 'disaster-links:context'],
        cwd=REPO,
        timeout=120,
    )

    if result.returncode != 0:
        raise RuntimeError('災害コンテキストの取得に失敗しました: ' + result.stderr.strip())

    return json.loads(result.stdout)


def fetch_volunteer_context():
    result = run_command(
        ['docker', 'compose', 'exec', '-T', 'app', 'php', 'artisan', 'volunteer-centers:context'],
        cwd=REPO,
        timeout=120,
    )

    if result.returncode != 0:
        raise RuntimeError('ボランティアセンター情報の取得に失敗しました: ' + result.stderr.strip())

    return json.loads(result.stdout)


def fetch_shelter_status_context():
    result = run_command(
        ['docker', 'compose', 'exec', '-T', 'app', 'php', 'artisan', 'shelter-status:context'],
        cwd=REPO,
        timeout=120,
    )

    if result.returncode != 0:
        raise RuntimeError('避難所開設状況のコンテキスト取得に失敗しました: ' + result.stderr.strip())

    return json.loads(result.stdout)


def build_prompt(disaster, limit):
    municipalities = '、'.join(disaster.get('municipalities', []))
    manual_links = disaster.get('manual_links') or []
    manual_urls = '\n'.join('- ' + item.get('url', '') for item in manual_links)

    return f'''あなたは災害情報のリサーチ担当です。次の災害について、被災者・支援者に役立つ最新情報をWEBで検索し、必ずJSON配列のみを出力してください。

災害名: {disaster.get('name', '')}
発生日: {disaster.get('event_date', '')}
年度: {disaster.get('year', '')}
対象市町村: {municipalities}

要件:
- 最新情報を最大{limit}件。
- 公式サイト（市町村、都道府県、国、気象庁、社会福祉協議会、公共機関、報道機関）を優先する。
- 被災者向け（支援制度、罹災証明、住宅、義援金、生活支援、道路・ライフライン）と支援者向け（ボランティア、寄附、支援制度）の情報を含める。
- URLは実在するhttpまたはhttpsのものだけ。推測でURLを作らない。
- 次の既存リンクと重複するURLは出力しない。
{manual_urls}
- 各要素は {{"description":"概要文。日付・期限・対象者があれば含め、200文字以内","url":"https://..."}} の形式。
- 出力はJSON配列のみ。コードブロック、説明文、見出しは付けない。'''


def build_volunteer_prompt(center):
    latest = center.get('latest_recruitment') or {}
    current = ''
    if latest.get('title') or latest.get('summary'):
        current = f"既存の募集情報: {latest.get('title', '')} / {latest.get('summary', '')}"

    return f'''あなたは災害ボランティア情報のリサーチ担当です。次の災害ボランティアセンターについて、WEBで直近のボランティア募集情報を確認し、必ずJSONオブジェクトのみを出力してください。

センター名: {center.get('name', '')}
市町村: {center.get('municipality', '')}
住所: {center.get('address', '')}
電話: {center.get('phone', '')}
公式URL: {center.get('url', '')}
{current}

要件:
- 公式サイト（災害ボランティアセンター、社会福祉協議会、自治体、全国社会福祉協議会など）を優先する。
- 現在の募集状況、募集内容、対象者、集合場所、受付時間、持ち物、事前登録の有無、活動日などを簡潔にまとめる。
- 最新の情報を反映し、日付・期限があれば概要に含める。
- URLは実在するhttpまたはhttpsのものだけ。推測でURLを作らない。
- 直近の募集情報が確認できない場合は、titleを「募集情報未確認」、summaryに確認できなかった旨を記載する。
- 出力は {{"title":"募集状況を表す簡潔な見出し","summary":"200文字以内の概要","url":"https://..."}} のJSONオブジェクトのみ。コードブロック、説明文、見出しは付けない。'''


def build_shelter_prompt(municipality):
    sources = '\n'.join(
        f"- {source.get('name', '')}: {source.get('url', '')}"
        for source in municipality.get('sources', [])
    )
    shelters = '\n'.join(
        f"{shelter.get('id')}|{shelter.get('name', '')}|{shelter.get('address') or ''}|{shelter.get('shelter_type', '')}"
        for shelter in municipality.get('shelters', [])
    )

    return f'''あなたは避難所の開設状況を確認するリサーチ担当です。次の市町村の公式防災情報をWEBで確認し、現在開設中（活動中）の避難所を特定して、必ずJSONオブジェクトのみを出力してください。

市町村: {municipality.get('prefecture', '')}{municipality.get('municipality', '')}
情報源:
{sources}

候補避難所（ID|名称|住所|種別）:
{shelters}

要件:
- 情報源の最新ページを確認する。避難所情報のページが別にある場合は、そのリンクも辿る。
- ページに「すべて閉鎖」「全て閉鎖」など、全避難所が閉鎖された旨がある場合は status を "closed"、active を空配列にする。
- 開設中の避難所だけを active に含める。閉鎖済み・過去に開設された避難所は含めない。
- 候補避難所から一致するものを選び、必ず候補のIDを返す。表記ゆれは名称・住所から判断する。
- 候補にない避難所を新しく追加しない。
- 避難者数が分かる場合は evacuees に整数で入れる。特記事項があれば note に入れる。
- 判断できない場合は status を "unknown"、active を空配列にする。推測で開設中としない。
- 出力は {{"status":"open|closed|unknown","source_name":"参照した情報源名","source_url":"参照したURL","active":[{{"id":123,"evacuees":12,"note":""}}]}} のJSONオブジェクトのみ。コードブロック、説明文、見出しは付けない。'''


def run_opencode(prompt, model):
    result = run_command(
        [str(OPENCODE), 'run', '--format', 'json', '--auto', '-m', model, prompt],
        cwd=Path('/tmp'),
        timeout=5400,
    )

    if result.returncode != 0:
        raise RuntimeError('opencodeの実行に失敗しました: ' + result.stderr.strip())

    parts = []

    for line in result.stdout.splitlines():
        line = line.strip()

        if not line:
            continue

        try:
            event = json.loads(line)
        except json.JSONDecodeError:
            continue

        if event.get('type') == 'text':
            text = event.get('part', {}).get('text')
            if text:
                parts.append(text)

    return '\n'.join(parts).strip()


def extract_json(answer):
    start = answer.find('[')
    end = answer.rfind(']')

    if start < 0 or end <= start:
        raise ValueError('AI応答からJSONを抽出できませんでした。')

    return json.loads(answer[start:end + 1])


def extract_json_object(answer):
    start = answer.find('{')
    end = answer.rfind('}')

    if start < 0 or end <= start:
        raise ValueError('AI応答からJSONオブジェクトを抽出できませんでした。')

    return json.loads(answer[start:end + 1])


def url_is_available(url):
    request = urllib.request.Request(
        url,
        headers={'User-Agent': 'Mozilla/5.0 (compatible; SaizDisasterLinks/1.0)'},
    )

    try:
        with urllib.request.urlopen(request, timeout=20) as response:
            response.read(1024)
            return response.status < 400
    except urllib.error.HTTPError as error:
        return error.code not in (404, 410)
    except Exception:
        return True


def fetch_source_text(url):
    request = urllib.request.Request(
        url,
        headers={'User-Agent': 'Mozilla/5.0 (compatible; SaizShelterStatus/1.0)'},
    )

    with urllib.request.urlopen(request, timeout=30) as response:
        raw = response.read()
        charset = response.headers.get_content_charset()

    for encoding in [charset, 'utf-8', 'cp932']:
        if not encoding:
            continue

        try:
            return raw.decode(encoding)
        except (LookupError, UnicodeDecodeError):
            continue

    return raw.decode('utf-8', errors='replace')


def detect_all_closed(text):
    markers = [
        'すべて閉鎖',
        '全て閉鎖',
        'すべて閉鎖しました',
        '全て閉鎖しました',
        '全避難所を閉鎖',
    ]

    return any(marker in text for marker in markers)


def normalize_links(items, manual_links, limit, verify_urls):
    manual_urls = {item.get('url') for item in manual_links}
    valid = []
    seen = set()

    for item in items:
        if not isinstance(item, dict):
            continue

        description = str(item.get('description', '')).strip()
        url = str(item.get('url', '')).strip()

        if not description or not url:
            continue

        if not url.startswith(('http://', 'https://')):
            continue

        if url in manual_urls or url in seen:
            continue

        if len(description) > 1000:
            description = description[:1000]

        if verify_urls and not url_is_available(url):
            continue

        seen.add(url)
        valid.append({'description': description, 'url': url})

        if len(valid) >= limit:
            break

    return valid


def ingest(disaster_id, links):
    TEMP_DIR.mkdir(parents=True, exist_ok=True)
    path = TEMP_DIR / f'disaster-{disaster_id}.json'
    path.write_text(json.dumps({'links': links}, ensure_ascii=False), encoding='utf-8')

    try:
        result = run_command(
            [
                'docker', 'compose', 'exec', '-T', 'app', 'php', 'artisan',
                'disaster-links:ingest', str(disaster_id),
                f'--file=storage/app/disaster-links/disaster-{disaster_id}.json',
            ],
            cwd=REPO,
            timeout=120,
        )
    finally:
        path.unlink(missing_ok=True)

    if result.returncode != 0:
        raise RuntimeError('リンクの更新に失敗しました: ' + result.stderr.strip())

    return result.stdout.strip()


def ingest_volunteer_center(center_id, recruitment):
    VOLUNTEER_TEMP_DIR.mkdir(parents=True, exist_ok=True)
    path = VOLUNTEER_TEMP_DIR / f'center-{center_id}.json'
    path.write_text(json.dumps(recruitment, ensure_ascii=False), encoding='utf-8')

    try:
        result = run_command(
            [
                'docker', 'compose', 'exec', '-T', 'app', 'php', 'artisan',
                'volunteer-centers:ingest', str(center_id),
                f'--file=storage/app/volunteer-centers/center-{center_id}.json',
            ],
            cwd=REPO,
            timeout=120,
        )
    finally:
        path.unlink(missing_ok=True)

    if result.returncode != 0:
        raise RuntimeError('ボランティア募集情報の更新に失敗しました: ' + result.stderr.strip())

    return result.stdout.strip()


def ingest_shelter_status(admin_code, payload):
    SHELTER_STATUS_TEMP_DIR.mkdir(parents=True, exist_ok=True)
    path = SHELTER_STATUS_TEMP_DIR / f'status-{admin_code}.json'
    path.write_text(json.dumps(payload, ensure_ascii=False), encoding='utf-8')

    try:
        result = run_command(
            [
                'docker', 'compose', 'exec', '-T', 'app', 'php', 'artisan',
                'shelter-status:ingest', str(admin_code),
                f'--file=storage/app/shelter-status/status-{admin_code}.json',
            ],
            cwd=REPO,
            timeout=120,
        )
    finally:
        path.unlink(missing_ok=True)

    if result.returncode != 0:
        raise RuntimeError('避難所開設状況の更新に失敗しました: ' + result.stderr.strip())

    return result.stdout.strip()


def acquire_lock(lock_file=LOCK_FILE):
    if lock_file.exists():
        age = time.time() - lock_file.stat().st_mtime

        if age < 21600:
            return False

        lock_file.unlink(missing_ok=True)

    lock_file.write_text(str(os.getpid()), encoding='utf-8')
    return True


def release_lock(lock_file=LOCK_FILE):
    lock_file.unlink(missing_ok=True)


def parse_chiba_portal_shelter_statuses(page):
    text = html.unescape(page)
    marker = text.find('id="togglePanelshelterOpen"')

    if marker < 0:
        return {}

    segment = text[marker:]
    table_end = segment.find('</table>')

    if table_end >= 0:
        segment = segment[:table_end]

    pattern = re.compile(
        r'<td class="tdHL"[^>]*>\s*(?:<a href="([^"]+)"[^>]*>)?\s*([^<]+?)\s*(?:</a>)?\s*</td>\s*'
        r'<td class="tdHR">(.*?)</td>',
        re.S,
    )
    statuses = {}

    for href, name, cell in pattern.findall(segment):
        name = html.unescape(name).strip()

        if not name:
            continue

        cell_text = html.unescape(re.sub(r'<[^>]+>', ' ', cell))

        if 'kaisetsu' in cell or '開設' in cell_text:
            status = 'open'
        elif 'heisa' in cell or '閉鎖' in cell_text:
            status = 'closed'
        else:
            status = 'unknown'

        statuses[name] = {
            'status': status,
            'detail_url': urljoin('https://www.bousai.pref.chiba.lg.jp/', href) if href else '',
        }

    return statuses


def parse_chiba_detail_open_shelters(page):
    text = html.unescape(page)
    text = re.sub(r'<script.*?</script>', ' ', text, flags=re.S)
    text = re.sub(r'<style.*?</style>', ' ', text, flags=re.S)
    text = re.sub(r'<[^>]+>', ' ', text)
    text = re.sub(r'[ \t]+', ' ', text)

    pattern = re.compile(
        r'([^\n：]+?)：避難所\s*(開設|閉鎖)\(\s*([^)]*)\)([^\n]*)',
        re.S,
    )
    shelters = []
    seen = set()

    for match in pattern.finditer(text):
        if match.group(2) != '開設':
            continue

        name = match.group(1).strip()

        if not name or name in seen:
            continue

        seen.add(name)
        evacuees = re.search(r'避難人数\(自主避難を含む\):\s*([0-9,]+)', match.group(4))
        shelters.append({
            'name': name,
            'evacuees': int(evacuees.group(1).replace(',', '')) if evacuees else None,
        })

    return shelters


def process_chiba_portal_shelter_statuses(municipalities, args, log_path):
    portal_url = 'https://www.bousai.pref.chiba.lg.jp/'

    try:
        page = fetch_source_text(portal_url)
    except Exception as error:
        log(f'千葉県ポータル: 情報源を取得できませんでした。{portal_url} ({error})', log_path)
        return

    statuses = parse_chiba_portal_shelter_statuses(page)

    if not statuses:
        log('千葉県ポータル: 避難所情報を解析できませんでした。', log_path)
        return

    for municipality in municipalities:
        admin_code = str(municipality.get('admin_code', ''))
        name = municipality.get('municipality', '')
        info = statuses.get(name)

        if not info:
            log(f"市町村 {admin_code}「{name}」: 千葉県ポータルに情報がありません。", log_path)
            continue

        status = info['status']
        detail_url = info.get('detail_url', '')

        if status == 'closed':
            payload = {
                'status': 'closed',
                'source_name': '千葉県防災ポータルサイト',
                'source_url': portal_url,
                'active': [],
            }
        elif status == 'open':
            if not detail_url:
                log(f"市町村 {admin_code}「{name}」: 開設情報の詳細URLがありません。", log_path)
                continue

            try:
                detail_page = fetch_source_text(detail_url)
            except Exception as error:
                log(f"市町村 {admin_code}「{name}」: 詳細情報を取得できませんでした。{detail_url} ({error})", log_path)
                continue

            active = parse_chiba_detail_open_shelters(detail_page)

            if not active:
                log(f"市町村 {admin_code}「{name}」: 開設中の避難所名を取得できませんでした。", log_path)
                continue

            payload = {
                'status': 'open',
                'source_name': '千葉県防災ポータルサイト',
                'source_url': detail_url,
                'active': active,
            }
        else:
            log(f"市町村 {admin_code}「{name}」: 千葉県ポータルの開設状況は不明です。", log_path)
            continue

        if args.dry_run:
            log(f"市町村 {admin_code}「{name}」: DryRunのため {status} / {len(payload['active'])}件を検出（更新なし）。", log_path)
            continue

        try:
            ingest_shelter_status(admin_code, payload)
            log(f"市町村 {admin_code}「{name}」: 千葉県ポータルの開設状況を更新しました（{status} / {len(payload['active'])}件）。", log_path)
        except Exception as error:
            log(f"市町村 {admin_code}「{name}」: {error}", log_path)


def parse_kumamoto_portal_open_shelters(payload, admin_code):
    items = payload.get('items') or []
    active = []
    seen = set()

    for item in items:
        if not str(item.get('municipalityCd', '')).startswith(admin_code):
            continue

        name = str(item.get('name', '')).strip()

        if not name or name in seen:
            continue

        if item.get('shelterStartTimestamp') and not item.get('shelterEndTimestamp'):
            seen.add(name)
            active.append({'name': name})

    return active


def process_kumamoto_portal_shelter_statuses(municipalities, args, log_path):
    data_url = 'https://portal.bousai.pref.kumamoto.jp/data/shelter/shelter.json'
    page_url = 'https://portal.bousai.pref.kumamoto.jp/?p=evacuation%2Fshelter'

    try:
        payload = json.loads(fetch_source_text(data_url))
    except Exception as error:
        log(f'防災情報くまもと: 避難所JSONを取得できませんでした。{data_url} ({error})', log_path)
        return

    if not isinstance(payload, dict) or not isinstance(payload.get('items'), list):
        log('防災情報くまもと: 避難所JSONの形式が不正です。', log_path)
        return

    for municipality in municipalities:
        admin_code = str(municipality.get('admin_code', ''))
        name = municipality.get('municipality', '')
        active = parse_kumamoto_portal_open_shelters(payload, admin_code)
        status = 'open' if active else 'closed'
        payload_item = {
            'status': status,
            'source_name': '防災情報くまもと 避難所情報',
            'source_url': page_url,
            'active': active,
        }

        if args.dry_run:
            log(f"市町村 {admin_code}「{name}」: DryRunのため {status} / {len(active)}件を検出（更新なし）。", log_path)
            continue

        try:
            ingest_shelter_status(admin_code, payload_item)
            log(f"市町村 {admin_code}「{name}」: 防災情報くまもとの開設状況を更新しました（{status} / {len(active)}件）。", log_path)
        except Exception as error:
            log(f"市町村 {admin_code}「{name}」: {error}", log_path)


def process_shelter_statuses(args, log_path):
    municipalities = fetch_shelter_status_context()
    chiba_municipalities = [
        municipality for municipality in municipalities
        if municipality.get('collector') == 'chiba_portal'
    ]

    if chiba_municipalities:
        process_chiba_portal_shelter_statuses(chiba_municipalities, args, log_path)

    kumamoto_municipalities = [
        municipality for municipality in municipalities
        if municipality.get('collector') == 'kumamoto_portal'
    ]

    if kumamoto_municipalities:
        process_kumamoto_portal_shelter_statuses(kumamoto_municipalities, args, log_path)

    municipalities = [
        municipality for municipality in municipalities
        if municipality.get('collector') not in ('chiba_portal', 'kumamoto_portal')
    ]

    for municipality in municipalities:
        admin_code = str(municipality.get('admin_code', ''))
        name = municipality.get('municipality', '')
        log(f"市町村 {admin_code}「{name}」の避難所開設状況を確認します。", log_path)

        closed_source = None

        for source in (municipality.get('sources') or [])[:2]:
            url = str(source.get('url', '')).strip()

            if not url.startswith(('http://', 'https://')):
                continue

            try:
                text = fetch_source_text(url)
            except Exception as error:
                log(f"市町村 {admin_code}: 情報源を取得できませんでした。{url} ({error})", log_path)
                continue

            if detect_all_closed(text):
                closed_source = source
                break

        if closed_source:
            payload = {
                'status': 'closed',
                'source_name': closed_source.get('name', ''),
                'source_url': closed_source.get('url', ''),
                'active': [],
            }

            if args.dry_run:
                log(f"市町村 {admin_code}: DryRunのため全避難所の閉鎖を検出（更新なし）。", log_path)
                continue

            try:
                ingest_shelter_status(admin_code, payload)
                log(f"市町村 {admin_code}: 全避難所を閉鎖として更新しました。", log_path)
            except Exception as error:
                log(f"市町村 {admin_code}: {error}", log_path)

            continue

        prompt = build_shelter_prompt(municipality)

        try:
            answer = run_opencode(prompt, args.model)
            result = extract_json_object(answer)
        except Exception as error:
            log(f"市町村 {admin_code}: {error}", log_path)
            continue

        status = str(result.get('status', 'unknown')).strip().lower()

        if status not in ('open', 'closed', 'unknown'):
            log(f"市町村 {admin_code}: 不明なstatusです。{status}", log_path)
            continue

        active = result.get('active') or []

        if not isinstance(active, list):
            active = []

        payload = {
            'status': status,
            'source_name': str(result.get('source_name', '')).strip(),
            'source_url': str(result.get('source_url', '')).strip(),
            'active': active,
        }

        if args.dry_run:
            log(f"市町村 {admin_code}: DryRunのため開設状況 {status} / {len(active)}件を検出（更新なし）。", log_path)
            continue

        try:
            ingest_shelter_status(admin_code, payload)
            log(f"市町村 {admin_code}: 避難所開設状況を更新しました（{status} / {len(active)}件）。", log_path)
        except Exception as error:
            log(f"市町村 {admin_code}: {error}", log_path)


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument('--dry-run', action='store_true')
    parser.add_argument('--limit', type=int, default=10)
    parser.add_argument('--model', default=os.environ.get('OPENCODE_MODEL', DEFAULT_MODEL))
    parser.add_argument('--log', default=str(DEFAULT_LOG))
    parser.add_argument('--no-url-check', action='store_true')
    parser.add_argument('--skip-links', action='store_true')
    parser.add_argument('--skip-volunteers', action='store_true')
    parser.add_argument('--skip-shelters', action='store_true')
    parser.add_argument('--disaster-id', type=int, help='指定した災害IDのみ最新情報を更新する')
    parser.add_argument('--center-prefecture', help='指定した都道府県コードのボランティアセンターのみ更新する')
    args = parser.parse_args()

    log_path = Path(args.log)

    if not acquire_lock():
        log('前回の処理が実行中のためスキップしました。', log_path)
        return 0

    try:
        log('処理を開始しました。', log_path)

        if not args.skip_links:
            disasters = fetch_context()

            if args.disaster_id:
                disasters = [
                    disaster for disaster in disasters
                    if int(disaster.get('id', 0)) == args.disaster_id
                ]

            for disaster in disasters:
                log(f"災害ID {disaster.get('id')}「{disaster.get('name')}」の検索を開始しました。", log_path)
                prompt = build_prompt(disaster, args.limit)

                try:
                    answer = run_opencode(prompt, args.model)
                    items = extract_json(answer)
                except Exception as error:
                    log(f"災害ID {disaster.get('id')}: {error}", log_path)
                    continue

                links = normalize_links(
                    items,
                    disaster.get('manual_links') or [],
                    args.limit,
                    not args.no_url_check,
                )

                if not links:
                    log(f"災害ID {disaster.get('id')}: 有効なリンクがありませんでした。", log_path)
                    continue

                if args.dry_run:
                    log(f"災害ID {disaster.get('id')}: DryRunのため {len(links)} 件を検出（更新なし）。", log_path)
                    continue

                try:
                    ingest(disaster.get('id'), links)
                    log(f"災害ID {disaster.get('id')}: {len(links)} 件のリンクを更新しました。", log_path)
                except Exception as error:
                    log(f"災害ID {disaster.get('id')}: {error}", log_path)

        if not args.skip_volunteers:
            centers = fetch_volunteer_context()

            if args.center_prefecture:
                centers = [
                    center for center in centers
                    if str(center.get('prefecture_code', '')) == str(args.center_prefecture)
                ]

            for center in centers:
                log(f"センターID {center.get('id')}「{center.get('name')}」の募集情報検索を開始しました。", log_path)
                prompt = build_volunteer_prompt(center)

                try:
                    answer = run_opencode(prompt, args.model)
                    recruitment = extract_json_object(answer)
                except Exception as error:
                    log(f"センターID {center.get('id')}: {error}", log_path)
                    continue

                title = str(recruitment.get('title', '')).strip()
                summary = str(recruitment.get('summary', '')).strip()
                url = str(recruitment.get('url', '')).strip()

                if not title and not summary:
                    log(f"センターID {center.get('id')}: 募集情報が空でした。", log_path)
                    continue

                if not url:
                    url = center.get('url') or ''

                if url and not url.startswith(('http://', 'https://')):
                    url = ''

                if url and not args.no_url_check and not url_is_available(url):
                    log(f"センターID {center.get('id')}: URLを確認できませんでした。{url}", log_path)
                    url = ''

                if args.dry_run:
                    log(f"センターID {center.get('id')}: DryRunのため募集情報を検出（更新なし）。", log_path)
                    continue

                try:
                    ingest_volunteer_center(center.get('id'), {
                        'title': title,
                        'summary': summary,
                        'url': url,
                    })
                    log(f"センターID {center.get('id')}: 募集情報を更新しました。", log_path)
                except Exception as error:
                    log(f"センターID {center.get('id')}: {error}", log_path)

        if not args.skip_shelters:
            try:
                process_shelter_statuses(args, log_path)
            except Exception as error:
                log(f'避難所開設状況の更新でエラー: {error}', log_path)

        log('処理を終了しました。', log_path)
        return 0
    except Exception as error:
        log(f'エラー: {error}', log_path)
        return 1
    finally:
        release_lock()


if __name__ == '__main__':
    sys.exit(main())
