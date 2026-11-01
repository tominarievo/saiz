# 移行ハーネス設計 (Migration Harness)

対象: `migration-plan.md` (v3) / `migration-execution-plan.md`  
目的: Laravel/PHP の各移行フェーズを、**再現可能・自動判定・証跡付き**で検証するための仕組みを設計する。

## 1. 設計原則

1. **ホスト環境に依存しない**: すべて Docker 内で実行する (ホスト PHP 8.5 は使わない)
2. **フェーズごとに再現可能**: PHP / Node / Laravel のバージョンをパラメータ化する
3. **ゲートで自動判定**: 人が見落とさないよう、G0〜G6 の合否をスクリプトで判定する
4. **セキュリティを継続検証**: 認可マトリクス・IDOR・XSS・ファイル攻撃を回帰テスト化する
5. **証跡を残す**: 実行コマンド、ログ、JUnit、カバレッジ、監査結果を `harness/reports/` に保存する
6. **失敗時に戻せる**: フェーズタグ + DB バックアップ + イメージタグでロールバック可能にする

## 2. アーキテクチャ

```
┌─────────────────────────────────────────────────────────────┐
│                        Developer / CI                        │
│  harness/scripts/gate.sh G3                                  │
└───────────────┬─────────────────────────────────────────────┘
                │
┌───────────────▼─────────────────────────────────────────────┐
│                   Docker Compose Harness                     │
│                                                              │
│  ┌────────────┐   ┌────────────┐   ┌──────────────────────┐  │
│  │ app        │   │ db-test    │   │ wp-stub (WireMock)   │  │
│  │ PHP x.y    │──▶│ MySQL 8.0  │   │ WordPress REST mock  │  │
│  │ Apache     │   │ saiz_test  │   └──────────────────────┘  │
│  │ Laravel    │   └────────────┘                             │
│  └─────┬──────┘                                              │
│        │                                                     │
│  ┌─────▼──────────────────────────────────────────────────┐   │
│  │ Harness Layers                                        │   │
│  │ 1. Preflight   2. Test   3. Security   4. Smoke       │   │
│  │ 5. Static      6. Data   7. Frontend   8. Evidence    │   │
│  └───────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
```

## 3. 環境マトリクス

| フェーズ | Laravel | PHP image | Node image | DB | 備考 |
|---|---|---|---|---|---|
| G0 (現状) | 8.83 | `php:8.1-apache-bookworm` | `node:16-bullseye` | MySQL 8.0 | PHP 7.4 は EOL/apt 404 のため 8.1 でベースライン |
| G2 | 8.83 | `php:8.1-apache-bookworm` | `node:16-bullseye` | MySQL 8.0 | PHP 引き上げ |
| G3 | 9.x | `php:8.1-apache-bookworm` | `node:16-bullseye` | MySQL 8.0 | collective 解体 |
| G4 | 10.x | `php:8.2-apache-bookworm` | `node:16-bullseye` | MySQL 8.0 | PHPUnit 10 |
| G5 | 12.x | `php:8.3-apache` | `node:16-bullseye` | MySQL 8.0 | 構造移行 |
| G6 | 13.x | `php:8.4-apache` | `node:16-bullseye` | MySQL 8.0 | 最終 |

> サーバー移行ゲート (G0〜G6) のフロントは **Node 16 / Mix 6 を維持**する。Node 24 / Vite / Vue 3 はフロント別プランのゲート (GF) で検証し、サーバー移行をブロックしない。

Dockerfile は `ARG PHP_IMAGE` を受け取る形に変更し、Compose の overlay で差し替える。

```dockerfile
# harness/docker/php/Dockerfile
ARG PHP_IMAGE=php:8.1-apache-bookworm
FROM ${PHP_IMAGE}
# git / unzip / curl / mbstring / pdo_mysql / rewrite を導入
```

```yaml
# harness/docker/compose.harness.yml
services:
  app:
    build:
      args:
        PHP_IMAGE: ${PHP_IMAGE:-php:8.1-apache-bookworm}
    depends_on:
      db-test:
        condition: service_healthy
    environment:
      DB_HOST: db-test
      DB_DATABASE: saiz_test
      APP_ENV: testing
      WORDPRESS_URL: http://wp-stub:8080
  db-test:
    image: mysql:8.0
    environment:
      MYSQL_DATABASE: saiz_test
      MYSQL_USER: saiz
      MYSQL_PASSWORD: saiz
      MYSQL_ROOT_PASSWORD: root
    command: --character-set-server=utf8mb4 --collation-server=utf8mb4_unicode_ci
    ports: ["33061:3306"]
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost", "-proot"]
      interval: 5s
      timeout: 5s
      retries: 20
  wp-stub:
    image: wiremock/wiremock:3
    volumes:
      - ./harness/wp-stub/mappings:/home/wiremock/mappings
    ports: ["8089:8080"]
```

> テスト DB は **MySQL 8** を必須とする。`FrontConditionTrait` の `DATE_FORMAT` など MySQL 固有 SQL があるため、SQLite では正しく検証できない。

## 4. ハーネスのレイヤー

### 4.1 Preflight (環境・依存の健全性)

チェック内容:
- `composer validate --strict`
- `composer install` 後の `vendor` と `composer.lock` の整合
- `php -v` / `php -m` (mbstring, pdo_mysql)
- `php artisan --version` (Laravel 8 には `about` がないため必須は `--version`。`about` は存在する場合のみ実行)
- `php artisan route:list` を保存 (監査は後述の PHP スクリプトで実施)
- `php artisan config:clear` / `view:clear`
- `php artisan storage:link`
- `php artisan migrate:fresh --seed` (テスト DB)
- `composer audit --format=json` (G0 は記録のみ。G1 以降は Critical/High を `security-audit-allowlist.json` と照合し、未許可のみ失敗)

フロントのビルド確認は Node サービスで別途実行する (サーバーゲートには含めない)。

スクリプト: `harness/scripts/preflight.sh`

### 4.2 Test (機能回帰)

- PHPUnit Unit / Feature
- テスト DB は MySQL (`saiz_test`)、`RefreshDatabase` を使用
- テスト用シーダー: 管理者 / 組織Aユーザー / 組織Bユーザー / 支援先 / 災害 / 日報 / 計画
- カバレッジは参考値 (Phase 0 は測定のみ、閾値は Phase 3 以降)

スクリプト: `harness/scripts/test.sh`

```bash
php artisan test --testsuite=Unit,Feature --log-junit "harness/reports/${GATE}/junit.xml"
```

### 4.3 Security (脆弱性回帰)

#### 認可マトリクス

ロール × ルート × 期待ステータスをデータプロバイダで検証する。

| ロール | 定義 | 期待 |
|---|---|---|
| guest | 未ログイン | 公開ルート以外は 302 (login) |
| admin | `organization_id = null` | 全管理ルート 200 |
| org_a | 組織A所属 | 自組織 200 / 他組織 403 |
| org_b | 組織B所属 | 自組織 200 / 他組織 403 |

対象ルート (最低限):
- `admin_users.*`, `users.*`, `organizations.*`, `shelters.*`, `disasters.*`
- `support_category1s.*`, `support_category2s.*`, `organization_seeds.*`
- `reports.*` (destroy 含む), `plans.*`, `information.*`, `plan_comments.*`
- `utils.front_file_download`, `upload.information.file`

テスト: `tests/Feature/Security/AuthzMatrixTest.php`

#### 攻撃ケース

| 分類 | ケース | 期待 |
|---|---|---|
| IDOR | 他組織の日報/支援先/組織/ユーザーを update/delete | 403 |
| Path traversal | `file_path=../.env`, `..%2f.env`, 絶対パス | 404/403 |
| 未認証 DL | `file_download` に未ログインでアクセス | 302/401 |
| Upload | `.php`, `.phtml`, `.svg`, `.html`, 二重拡張子, MIME 偽装, サイズ超過 | 422 |
| XSS | `<script>`, `<img onerror>`, `javascript:` URL, `</script>` in `file_data` | エスケープ/除去 |
| CSRF | トークンなし POST | 419 (実 HTTP スモークで検証) |
| 認可昇格 | 一般ユーザーが `admin_users` を作成/更新/削除 | 403 |
| 情報漏洩 | エラーページにスタックトレース (`APP_DEBUG=false`) | 非表示 |

テスト:
- `tests/Feature/Security/IdorTest.php`
- `tests/Feature/Security/FileDownloadTest.php`
- `tests/Feature/Security/FileUploadTest.php`
- `tests/Feature/Security/XssTest.php`

スクリプト: `harness/scripts/security.sh`

#### ルート認可監査

`route:list` を解析し、公開許可リスト以外のルートに `auth` ミドルウェアがあることを検証する。バージョン差異を避けるため、`Route::getRoutes()` を直接走査する PHP スクリプトを使う (`--json` の有無に依存しない)。

```bash
php harness/scripts/route-audit.php harness/reports/${GATE}/routes.json
```

公開許可リスト:
- `login` (GET/POST)
- `new_passwords.*`, `password_regenerations.*`, `new_passwords.complete`, `password_regenerations.complete`
- `refresh-csrf`
- 静的アセット

### 4.4 Smoke (実 HTTP)

- `curl` ベースの HTTP スモーク (高速)
- 必要に応じて Playwright で主要画面の E2E (Phase 3 以降)
- 対象: ログイン → ホーム → 日報一覧/詳細/作成 → 計画 → お知らせ → 支援先 → 組織 → 管理 → CSV DL → ファイル UP/DL
- 認証はテストユーザーでログインし、CSRF トークンを取得して送信
- 失敗時はスクリーンショットとレスポンスボディを保存

スクリプト: `harness/scripts/smoke.sh`、定義: `harness/smoke/routes.tsv`

### 4.5 Static Analysis / Format

- Larastan (`phpstan analyse`) を導入。Phase 0 はレベル 3、G5 までにレベル 5
- Laravel Pint (`pint --test`) でフォーマット統一
- `composer audit` で既知脆弱性を検出

スクリプト: `harness/scripts/static.sh`

### 4.6 Data (DB)

- `php artisan migrate:fresh --seed` でテスト DB を初期化
- 本番相当のバックアップ/復元手順を別途検証
- スキーマ変更がある場合は `schema:dump` の差分を確認 (Phase 4 以降)

スクリプト: `harness/scripts/db-reset.sh`

### 4.7 Frontend (別ゲート GF)

サーバー移行とは切り離し、Node サービスで実行する。

- `npm ci`
- `npm run production` (Mix) / Vite 移行後は `npm run build`
- CSS/JS の生成物が `public/` に出力されることを確認
- Node 24 / Vite / Vue 3 はフロント別プランの GF ゲートで検証する
- GF が赤でも G0〜G6 のサーバー移行ゲートはブロックしない (ただしリリース判定では GF を確認)

```bash
docker compose -f docker-compose.yml -f harness/docker/compose.harness.yml run --rm node sh -c "npm ci && npm run production"
```

スクリプト: `harness/scripts/frontend.sh` (ホストまたは CI から Node サービスに対して実行)

### 4.8 Evidence (証跡)

各ゲートの実行結果を以下に保存する。

```
harness/reports/Gx/
  summary.json        # ゲート判定 (pass/fail, 実行コマンド, 時刻, バージョン)
  preflight.log
  test.log
  junit.xml
  security.log
  route-audit.json
  smoke.log
  screenshots/
  composer-audit.json
  versions.txt        # PHP / Laravel / Node / MySQL
```

`summary.json` の例:

```json
{
  "gate": "G3",
  "status": "pass",
  "started_at": "2026-11-27T10:00:00+09:00",
  "php": "8.1.31",
  "laravel": "9.52.16",
  "node": "16.20.2",
  "checks": {
    "preflight": "pass",
    "test": "pass",
    "security": "pass",
    "smoke": "pass",
    "static": "pass",
    "frontend": "pass"
  }
}
```

## 5. ゲートランナー

`harness/scripts/gate.sh <G0|G1|...|G6>` が以下を順に実行し、1つでも失敗したら非0で終了する。

```
1. preflight
2. db-reset
3. test
4. security (G1 以降必須)
5. static
6. db-reset (シーダー込み)
7. smoke
8. evidence 収集
9. summary.json 出力
```

フロント (GF) は別実行とし、サーバーゲートの合否には含めない。

### ゲート別の必須チェック

| ゲート | preflight | test | security | smoke | static |
|---|---|---|---|---|---|
| G0 | ○ | ○ | - | ○ | - |
| G1 | ○ | ○ | ○ | ○ | ○ |
| G2 | ○ | ○ | ○ | ○ | ○ |
| G3 | ○ | ○ | ○ | ○ | ○ |
| G4 | ○ | ○ | ○ | ○ | ○ |
| G5 | ○ | ○ | ○ | ○ | ○ |
| G6 | ○ | ○ | ○ | ○ | ○ |

補足:
- G0 は現状のベースライン取得のため、セキュリティテストは未導入でもよい
- G1 以降はセキュリティテストを必須とする
- G3 は `Form::` / `HTML::` の残存ゼロ、collective 削除を追加チェック
- G5 は旧 Kernel / RouteServiceProvider / dbal の残存ゼロを追加チェック
- G6 は `config:cache` / `route:cache` / `view:cache` 起動を追加チェック
- GF (フロント) は Node 16 でビルド成功、Node 24 / Vite は別プランで検証

## 6. CI 設計

GitHub Actions を想定。フェーズブランチごとに PHP イメージを切り替える。

```yaml
# .github/workflows/migration.yml
name: migration-harness
on:
  push:
    branches: [migration/**]
  pull_request:
    branches: [migration/**]
jobs:
  gate:
    runs-on: ubuntu-latest
    strategy:
      matrix:
        include:
          - php_image: php:8.1-apache-bookworm
            gate: G2
          - php_image: php:8.1-apache-bookworm
            gate: G3
          - php_image: php:8.2-apache-bookworm
            gate: G4
          - php_image: php:8.3-apache
            gate: G5
          - php_image: php:8.4-apache
            gate: G6
    steps:
      - uses: actions/checkout@v4
      - name: Build and start services
        run: |
          PHP_IMAGE=${{ matrix.php_image }} docker compose -f docker-compose.yml -f harness/docker/compose.harness.yml build app
          PHP_IMAGE=${{ matrix.php_image }} docker compose -f docker-compose.yml -f harness/docker/compose.harness.yml up -d db-test wp-stub
      - name: Gate
        run: |
          PHP_IMAGE=${{ matrix.php_image }} docker compose -f docker-compose.yml -f harness/docker/compose.harness.yml run --rm app sh harness/scripts/gate.sh ${{ matrix.gate }}
      - uses: actions/upload-artifact@v4
        if: always()
        with:
          name: reports-${{ matrix.gate }}
          path: harness/reports/${{ matrix.gate }}
      - name: Stop services
        if: always()
        run: docker compose -f docker-compose.yml -f harness/docker/compose.harness.yml down -v

  frontend:
    runs-on: ubuntu-latest
    # フロント別プランの GF ゲート。サーバー移行をブロックしないため、
    # Vite 移行完了までは continue-on-error とする。
    continue-on-error: true
    steps:
      - uses: actions/checkout@v4
      - name: Build frontend (Node 16 / Mix 6)
        run: |
          docker compose -f docker-compose.yml -f harness/docker/compose.harness.yml run --rm node sh -c "npm ci && npm run production"
```

補足:
- MySQL と WP スタブは Compose overlay 側で起動する (GitHub Actions の `services` は使わない。ポート競合を避けるため)
- 実際のブランチでは該当ゲートのみ実行する (matrix を段階的に有効化)
- `composer.lock` と `package-lock.json` をキャッシュキーに含める
- 失敗時も artifact を必ずアップロードする

## 7. ディレクトリ構成

```
harness/
  README.md
  security-audit-allowlist.json
  docker/
    php/Dockerfile
    compose.harness.yml
    compose.local.yml
  wp-stub/
    mappings/
      posts.json
      media.json
  scripts/
    preflight.sh
    test.sh
    security.sh
    smoke.sh
    static.sh
    frontend.sh
    db-reset.sh
    route-audit.php
    audit-check.php
    gate.sh
    evidence.sh
  smoke/
    routes.tsv
    login.sh
  reports/
    .gitkeep
tests/
  Feature/
    Security/
      AuthzMatrixTest.php
      IdorTest.php
      FileDownloadTest.php
      FileUploadTest.php
      XssTest.php
    Smoke/
      CriticalFlowTest.php
```

## 8. 実装手順 (Phase 0 で完了させる)

1. `harness/docker/php/Dockerfile` を `ARG PHP_IMAGE` 化
2. `harness/docker/compose.harness.yml` を作成 (db-test / wp-stub / app の環境変数)
3. `harness/scripts/preflight.sh` と `db-reset.sh` を実装
4. `phpunit.xml` を MySQL テスト DB 用に更新
5. テスト用シーダーとロール別ユーザーを作成
6. `harness/scripts/test.sh` / `static.sh` / `frontend.sh` を実装
7. `route-audit.php` を実装
8. `AuthzMatrixTest` の雛形を作成 (G0 では skip 可)
9. `smoke.sh` と `routes.tsv` を実装 (まずはログイン + 主要3画面)
10. `gate.sh` と `evidence.sh` を実装
11. CI ワークフローを追加
12. G0 を実行し、ベースラインを `harness/reports/G0/` に保存

## 9. ロールバック設計

- 各ゲート通過時に `migration/Gx` タグを打つ
- Docker イメージは `saiz-app:Gx` としてタグ付け (必要に応じてレジストリへ)
- DB はゲート前にバックアップ (`mysqldump`) を取得
- 失敗時は前ゲートのタグ + バックアップ + イメージで復旧
- ロールバック手順を `docs/rollback.md` に記録 (実装時に作成)

## 10. 制約と将来拡張

- 初期段階ではブラウザ E2E を導入しない。Phase 3 以降に Playwright を追加
- WordPress は WireMock で代替。実環境との結合テストは別途
- テスト DB は MySQL 8.0 固定。本番の MySQL バージョンが異なる場合はマトリクスに追加
- カバレッジ閾値は Phase 3 以降に設定 (初期は測定のみ)
- フロントエンドの Vue 3 / Vite 移行は別ハーネス (別ドキュメント) とする
- AI エージェントが実行する場合も、`gate.sh` の非0終了を「未完了」とみなし、証跡なしの完了報告を禁止する
