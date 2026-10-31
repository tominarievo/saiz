# Migration Harness

`migration-harness.md` の設計に基づく検証ハーネスです。すべて Docker 内で実行します。

## 前提

- Docker Desktop / Docker Engine + Docker Compose v2
- ホスト PHP は使いません
- テスト DB は MySQL 8 (`saiz_test`)。SQLite は使用しません

## 実行方法

```bash
# G0 (ベースライン)
docker compose -f docker-compose.yml -f harness/docker/compose.harness.yml build app
docker compose -f docker-compose.yml -f harness/docker/compose.harness.yml up -d db-test wp-stub
docker compose -f docker-compose.yml -f harness/docker/compose.harness.yml run --rm app bash harness/scripts/gate.sh G0
```

PHP バージョンを切り替える場合:

```bash
PHP_IMAGE=php:8.1-apache-bullseye docker compose -f docker-compose.yml -f harness/docker/compose.harness.yml build app
PHP_IMAGE=php:8.1-apache-bullseye docker compose -f docker-compose.yml -f harness/docker/compose.harness.yml run --rm app bash harness/scripts/gate.sh G2
```

フロント (別ゲート):

```bash
bash harness/scripts/frontend.sh
```

## ゲート

| ゲート | 内容 |
|---|---|
| G0 | ベースライン (preflight / test / smoke / 証跡) |
| G1 | セキュリティ (認可マトリクス / 攻撃テスト / ルート監査 / composer audit) |
| G2 | PHP 8.1 |
| G3 | Laravel 9 + `Form::` 解体 |
| G4 | Laravel 10 + PHPUnit 10 |
| G5 | Laravel 12 + 構造移行 |
| G6 | Laravel 13 最終 |

## スモークテストの認証情報

`DevDataSeeder` の開発用ユーザーを既定値として使用します。環境変数で上書きできます。

```bash
SMOKE_USERNAME=... SMOKE_PASSWORD=... REQUIRE_AUTH=1 bash harness/scripts/smoke.sh
```

## 証跡

`harness/reports/<GATE>/` にログ、JUnit、ルート監査、`summary.json` を保存します。ゲート通過時に `migration/Gx` タグを打ち、レポートをコミットしてください。

## 注意

- `harness/scripts/preflight.sh` は `.env` が無ければ `.env.example` から作成し、`APP_KEY` を生成します
- `harness/scripts/preflight.sh` は `composer install` を実行します (vendor と lock の同期)
- `composer.json` の `minimum-stability` 変更後は、Docker 内で `composer update --lock` を実行して content-hash を更新してください
- G0 ではセキュリティテストを実行しません (G1 から必須)
- G0 の `composer audit` は記録のみ。G1 以降は脆弱性があれば失敗します
- PHPStan / Pint は未導入のため、現状はスキップされます (導入後に必須化)
