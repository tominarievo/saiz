#!/usr/bin/env python3

import functools
import ipaddress
import os
import re
import socket
import time
import urllib.error
import urllib.request
from urllib.parse import urlparse
from urllib.robotparser import RobotFileParser

ALLOWED_HOSTS = {
    'jvoad.jp',
    'kvoad.com',
    'cvoad.jp',
    'portal.bousai.pref.kumamoto.jp',
    'd2800000147bueaq.my.salesforce-sites.com',
    'pref-chiba-bousai.my.salesforce-sites.com',
    'geoshape.ex.nii.ac.jp',
}
ALLOWED_HOST_SUFFIXES = (
    '.go.jp',
    '.lg.jp',
    '.or.jp',
    '.jvoad.jp',
    '.kvoad.com',
    '.cvoad.jp',
)
ALLOWED_HOST_PATTERN = re.compile(
    r'^(?:www\.)?(?:city|town|vill|pref)\.[a-z0-9-]+(?:\.[a-z0-9-]+)*\.jp$',
    re.IGNORECASE,
)
DEFAULT_USER_AGENT = 'SaizBot/1.0 (disaster information research)'
MIN_INTERVAL_SECONDS = float(os.environ.get('DISASTER_CRAWLER_MIN_INTERVAL_SECONDS', '1'))
TIMEOUT_SECONDS = float(os.environ.get('DISASTER_CRAWLER_TIMEOUT', '20'))
MAX_REDIRECTS = 3
LAST_REQUEST_AT = {}
ROBOTS_CACHE = {}


class SourcePolicyError(Exception):
    pass


def user_agent():
    return os.environ.get('DISASTER_CRAWLER_USER_AGENT', DEFAULT_USER_AGENT)


def normalize_host(host):
    host = (host or '').lower().rstrip('.')

    return re.sub(r'^www\.', '', host)


def is_allowed_host(host):
    normalized = normalize_host(host)

    if not normalized:
        return False

    if normalized in {normalize_host(item) for item in ALLOWED_HOSTS}:
        return True

    if normalized.endswith(ALLOWED_HOST_SUFFIXES):
        return True

    return bool(ALLOWED_HOST_PATTERN.match(normalized))


@functools.lru_cache(maxsize=256)
def resolve_host(host):
    try:
        infos = socket.getaddrinfo(host, None)
    except OSError as error:
        raise SourcePolicyError('DNS解決に失敗しました: ' + str(error))

    addresses = sorted({info[4][0] for info in infos if info[4]})

    if not addresses:
        raise SourcePolicyError('DNS解決結果がありません: ' + host)

    return tuple(addresses)


def is_public_ip(ip):
    try:
        address = ipaddress.ip_address(ip)
    except ValueError:
        return False

    if address.is_private or address.is_loopback or address.is_link_local:
        return False

    if address.is_multicast or address.is_reserved or address.is_unspecified:
        return False

    return address.is_global


def assert_allowed_url(url):
    parsed = urlparse(url)

    if parsed.scheme not in ('http', 'https'):
        raise SourcePolicyError('http/https以外のURLです: ' + url)

    if parsed.username or parsed.password:
        raise SourcePolicyError('ユーザー情報を含むURLです: ' + url)

    host = (parsed.hostname or '').lower().rstrip('.')

    if not host:
        raise SourcePolicyError('ホストがありません: ' + url)

    try:
        ipaddress.ip_address(host)
        raise SourcePolicyError('IPアドレス直指定のURLです: ' + url)
    except ValueError:
        pass

    if parsed.port and parsed.port not in (80, 443):
        raise SourcePolicyError('許可されていないポートです: ' + url)

    if not is_allowed_host(host):
        raise SourcePolicyError('取得先として許可されていないホストです: ' + host)

    for address in resolve_host(host):
        if not is_public_ip(address):
            raise SourcePolicyError('取得先が内部ネットワークを指しています: ' + host + ' (' + address + ')')


def is_allowed_url(url):
    try:
        assert_allowed_url(url)

        return True
    except Exception:
        return False


def throttle(host):
    now = time.monotonic()
    last = LAST_REQUEST_AT.get(host, 0.0)
    wait = MIN_INTERVAL_SECONDS - (now - last)

    if wait > 0:
        time.sleep(wait)

    LAST_REQUEST_AT[host] = time.monotonic()


def robots_parser(url):
    parsed = urlparse(url)
    origin = f'{parsed.scheme}://{parsed.hostname}'

    if origin in ROBOTS_CACHE:
        return ROBOTS_CACHE[origin]

    robots_url = origin + '/robots.txt'
    parser = None

    try:
        assert_allowed_url(robots_url)
        throttle(parsed.hostname)
        request = urllib.request.Request(robots_url, headers={'User-Agent': user_agent()})

        try:
            with urllib.request.urlopen(request, timeout=TIMEOUT_SECONDS) as response:
                if response.status < 400:
                    text = response.read(200_000).decode('utf-8', errors='replace')
                    parser = RobotFileParser()
                    parser.set_url(robots_url)
                    parser.parse(text.splitlines())
        except urllib.error.HTTPError as error:
            if error.code in (401, 403):
                parser = False
    except Exception:
        parser = None

    ROBOTS_CACHE[origin] = parser

    return parser


def robots_allowed(url):
    parser = robots_parser(url)

    if parser is None:
        return True

    if parser is False:
        return False

    try:
        return parser.can_fetch(user_agent(), url)
    except Exception:
        return True


class SafeRedirectHandler(urllib.request.HTTPRedirectHandler):
    max_redirections = MAX_REDIRECTS

    def redirect_request(self, req, fp, code, msg, headers, newurl):
        assert_allowed_url(newurl)

        return super().redirect_request(req, fp, code, msg, headers, newurl)


def open_request(url, method='HEAD'):
    assert_allowed_url(url)

    if not robots_allowed(url):
        raise SourcePolicyError('robots.txtで取得が禁止されています: ' + url)

    parsed = urlparse(url)
    throttle(parsed.hostname)

    opener = urllib.request.build_opener(SafeRedirectHandler)
    request = urllib.request.Request(
        url,
        headers={'User-Agent': user_agent(), 'Accept': '*/*'},
        method=method,
    )

    return opener.open(request, timeout=TIMEOUT_SECONDS)


def safe_head(url):
    try:
        with open_request(url, 'HEAD') as response:
            if 200 <= response.status < 400:
                return True, f'HTTP {response.status}'

            return False, f'HTTP {response.status}'
    except urllib.error.HTTPError as error:
        if error.code == 405:
            try:
                with open_request(url, 'GET') as response:
                    return (200 <= response.status < 400), f'HTTP {response.status}'
            except Exception as nested:
                return False, str(nested)

        return False, f'HTTP {error.code}'
    except Exception as error:
        return False, str(error)


def safe_get_text(url, max_bytes=2_000_000):
    with open_request(url, 'GET') as response:
        raw = response.read(max_bytes + 1)
        charset = response.headers.get_content_charset()

    if len(raw) > max_bytes:
        raise SourcePolicyError('レスポンスが大きすぎます: ' + url)

    for encoding in [charset, 'utf-8', 'cp932']:
        if not encoding:
            continue

        try:
            return raw.decode(encoding)
        except (LookupError, UnicodeDecodeError):
            continue

    return raw.decode('utf-8', errors='replace')
