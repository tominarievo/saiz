# Laravel / PHP 最新版対応 移行プラン (v3)

> 改訂履歴:
> - v3 (2026-10-26): レビュー指摘を反映。`laravelcollective/html` の解体を Phase 2 へ前倒し、`str_slug` の2箇所修正、vendor/lock 同期、`env()` 監査の完全化、認可漏れ追加、依存バージョン更新の具体化、テストDBをMySQLに固定、Node 24 LTS、日程を再算定。実行計画とハーネス設計を別ドキュメント化。
> - v2 (2026-10-26): 目標を Laravel 13 に修正、セキュリティ修正を先行化、Docker 更新・依存整理を追記。
> - v1 (2026-10-26): 初版作成。

## 目的

現行の Laravel 8 / PHP 7.2.5|^8.0 アプリケーションを、サポートが有効な最新の Laravel と PHP に移行する。併せて、レビューで検出したセキュリティ脆弱性とテスト不足を解消し、再発防止のための検証ハーネスを整備する。

関連ドキュメント:
- 実行計画: `migration-execution-plan.md`
- ハーネス設計: `migration-harness.md`

## 目標バージョン (最終到達点)

| 対象 | 現行 | 目標 | 備考 |
|---|---|---|---|
| Laravel | 8.83.3 (composer.lock) | **13.x** | Laravel 12 は 2027-02-24 にセキュリティサポート終了。13 が最終目標 |
| PHP (Docker) | 8.1 (`docker/php/Dockerfile`) | **8.4** | 2026-10-29 に php:7.4-bullseye から php:8.1-apache-bookworm へ更新済み (bullseye の apt 404 対策)。Laravel 13 は PHP 8.3〜8.5 が必須 |
| Node | 16 (`node:16-bullseye`) | **24 LTS** | Node 22 はメンテナンス期。2026-10 時点の最新 LTS は 24 |
| MySQL | 8.0 | 8.0 維持 | テストも MySQL 8 を使用 (後述) |
| フロント | Vue 2 + Laravel Mix 6 | Vue 3 移行は別プラン | 本プランはサーバー側移行を主対象 |

サポート情報 (2026-10-26 時点):

| Laravel | 対応 PHP | セキュリティサポート期限 |
|---|---|---|
| 8 | 7.3〜8.1 | **終了済み** |
| 9 | 8.0.2〜8.2 | **終了済み** |
| 10 | **8.1**〜8.3 | **終了済み** (2025-02-04) |
| 11 | 8.2〜8.4 | **終了済み** (2026-03-12) |
| 12 | 8.2〜8.5 | 2027-02-24 |
| 13 | **8.3〜8.5** | 2028-03-17 |

> 「Laravel 11 (LTS)」「PHP 8.3 LTS」という表現は誤り。Laravel 11 は既に EOL。

## 基本方針

1. **セキュリティホットフィックスを最優先**で現行スタック上に適用する (移行完了を待たない)
2. Laravel のメジャーバージョンを順に上げる。EOL 済みの中間バージョン (9/10/11) には**滞留せず通過**する
3. 各フェーズの終了時に**タグ付けしたコミットポイント**を打ち、ロールバック可能にする
4. 各フェーズの検証は `migration-harness.md` のゲート (G0〜G6) を通過させる
5. テストは Phase 0 でハーネスを整備し、以降のフェーズで拡充する

## 移行パス概要

| フェーズ | Laravel | PHP (Docker) | 目安 | 主な内容 |
|---|---|---|---|---|
| 0. 準備 | 8 | 8.1 | 3-5日 | vendor 同期、テストDB、ハーネス構築、ベースライン取得 |
| 0.5 セキュリティ | 8 | 8.1 | 5-7日 | 認可・XSS・ファイル・env() のホットフィックス |
| 1. PHP 8.1 | 8 | 8.1 | 2-3日 | str_slug、implicit nullable、依存調整 |
| 2. Laravel 9 | 9 | 8.1 | 6-10日 | **laravelcollective/html 解体**、依存更新、ルート記法 |
| 3. Laravel 10 | 10 | 8.2 | 3-5日 | 非推奨 API、PHPUnit 10、Faker 移行 |
| 4. Laravel 11 → 12 | 12 | 8.3 | 8-12日 | アプリ構造移行、Kernel/RouteServiceProvider 解体、DBAL 削除 |
| 5. Laravel 13 | 13 | 8.4 | 2-3日 | 最終版、config:cache 検証、残存 deprecation 解消 |

合計: **約30〜46日** (バッファ込みで 35〜50日)。並行作業や既存コードの品質により変動する。

## Phase 0: 準備 (3-5日)

- [ ] Docker 開発環境を利用する (ホスト PHP 8.5 は使わない。Docker は `php:8.1-apache-bookworm` に更新済み)
- [x] `docker/php/Dockerfile` を `php:8.1-apache-bookworm` に更新 (Debian bullseye の apt 404 対策)
- [ ] **vendor と composer.lock の不整合を解消**: ホスト `vendor/` に `laravel/helpers` と `laravelcollective/html` が存在しないため、Docker 内で `composer validate --strict` と `composer install` を実行して再構築
- [ ] Docker 内で `php artisan --version` と `php artisan test` が通ることを確認 (Laravel 8 には `about` コマンドがないため `--version` を使う)
- [ ] テスト用 DB を **MySQL 8** で分離 (`saiz_test`)。SQLite は `DATE_FORMAT` など MySQL 固有 SQL のため使用不可
- [ ] `phpunit.xml` に `DB_CONNECTION=mysql` / `DB_DATABASE=saiz_test` を追加
- [ ] 主要画面のスモークテストチェックリストを作成 (ログイン / 日報 / 計画 / お知らせ / 支援先 / 組織 / 管理 / CSV DL / ファイル UP・DL / WP連携)
- [ ] `php artisan storage:link` と `php artisan route:list` のベースラインを保存 (認可監査は `route-audit.php` で実施)
- [ ] DB バックアップ・復元手順を文書化
- [ ] Git ブランチ戦略とタグ規約を決定 (`hotfix/security`, `migration/php81`, `migration/laravel9`, タグ `migration/G0`〜`G6`)
- [x] `composer.json` の `minimum-stability: dev` を削除し、`composer update --lock` で lock の content-hash を更新 (Docker 上で実施済み)
- [x] `migration-harness.md` のハーネスを構築し、G0 を通過させる (2026-10-30 pass)

## Phase 0.5: セキュリティホットフィックス (現行スタック上で最優先) (5-7日)

Laravel 8 のまま先に適用する。どのバージョンにも引き継がれる。

- [x] `AdminUserController` に管理者認可を追加 (現状、管理者アカウントの CRUD が無認可)
- [x] CRUD の認可漏れ (IDOR) を修正:
  - `ShelterController` / `OrganizationController` (edit のみ authorize、update/destroy が無い)
  - `DisasterController` / `SupportCategory1Controller` / `SupportCategory2Controller`
  - `UserController` (edit/update/destroy) / `OrganizationSeedController`
  - `InformationController` (create/store/edit/update/destroy。WP 投稿も含む)
  - `ReportController::destroy`
  - `PlanCommentController::store` / `delete` (現状、任意のコメントを削除可能)
- [x] `UserController::store` の `organization_id` を認可済みの値に限定
- [x] `UtilController::frontFileDownload` を認証必須化し、`file_path` を許可リスト/正規化で検証 (path traversal 対策)
- [x] `WysiwygFileUploadController` に拡張子・MIME・サイズ検証を追加
- [x] `Information::getEditedContent()` の raw HTML 返却を HTMLPurifier でサニタイズ
- [x] `file_data` のサーバー側バリデーション (JSON 形式) と Blade の `@json` 出力を追加
- [x] `env()` の完全監査と `config()` 化:
  - `InformationController` (WordPress)
  - `UserController` (MAIL_BCC_DEFAULT)
  - `AppServiceProvider` (SSL_USE)
  - `LoginController` (MY_DECAY_MINUTES / MY_MAX_ATTEMPTS)
- [ ] WordPress 連携の `curl` 直書きを Laravel HTTP クライアント (`Http::withBasicAuth`) に置換し、テスト可能にする
- [x] 欠落している `App\Http\Requests\UploadImageFileRequest` を追加
- [ ] 本番設定として `APP_DEBUG=false`、`APP_ENV` を整理
- [x] G1 (セキュリティゲート) を通過 (2026-10-31 pass)

## Phase 1: PHP 8.1 引き上げ (Laravel 8 維持) (2-3日)

- [x] `docker/php/Dockerfile` を `php:8.1-apache-bookworm` に変更 (Phase 0 で更新済み)
- [x] `composer.json` の PHP 制約を `^8.1` に変更
- [x] `laravel/helpers` を削除し、ヘルパー呼び出しを置換:
  - `str_slug()` → `Illuminate\Support\Str::slug()` (**`config/cache.php:91` と `config/session.php:127` の2箇所**)
  - `str_random()` → `Illuminate\Support\Str::random()` (`database/factories/UserFactory.php`)
- [x] 暗黙的 nullable を明示化 (`app/Shelter.php:61,119`)
- [x] `laravelcollective/html` は Phase 2 で解体するため、この段階では v6.3 を維持
- [x] `fideloper/proxy` は Phase 2 で削除するため、この段階では維持
- [x] Carbon 系依存を PHP 8.1 対応版に更新
- [x] G2 (PHP 8.1 ゲート) を通過 (2026-10-31 pass)

**完了条件:** PHP 8.1 上で artisan・テスト・主要画面が動作し、`str_slug` と implicit nullable の警告がゼロ。

## Phase 2: Laravel 9 移行 + Blade フォーム解体 (6-10日)

- [x] `laravel/framework` を 9.x に更新 (`composer require laravel/framework:^9.0 -W`)
- [x] **`laravelcollective/html` を解体 (このフェーズの必須タスク)**
  - v6.3.0 の対応上限は **Laravel 9** (`illuminate ^6|^7|^8|^9`)。Laravel 10 では動作しないため、Phase 3 前に依存から消す
  - 今回は `App\Support\Form` の互換シムを実装し、`Form::` 呼び出しを維持したままパッケージを削除
  - 標準 HTML + `@csrf` + `@method` への完全置換は後続フェーズで段階的に実施
  - 完了確認: `laravelcollective/html` が composer.lock から消え、`dependency-check.sh` が通る
- [ ] ルート定義の文字列記法をクラス記法へ移行開始 (`'OverviewController@index'` → `[OverviewController::class, 'index']`)
- [ ] `RouteServiceProvider` の namespace 廃止に対応
- [ ] `config/*.php` と Middleware グループの変更を反映
- [x] 依存更新:
  - `league/flysystem-aws-s3-v3` **^1.0 → ^3.0** (Laravel 9 は Flysystem 3 が必須)
  - `doctrine/dbal` **^2.8 → ^3.0**
  - `nunomaduro/collision` **^5 → ^6**
  - `laravel/ui` は認証トレイトの提供元のため ^4 で維持
  - `fideloper/proxy` を削除し、Laravel 標準の `TrustProxies` に移行
  - `guzzlehttp/guzzle` / `barryvdh/laravel-ide-helper` / `laravel/tinker` / `mockery` を対応版に更新
- [x] `php artisan migrate` / seed / テスト / スモークテスト
- [x] G3 (Laravel 9 ゲート) を通過 (2026-10-31 pass)

**完了条件:** Laravel 9 + PHP 8.1 で全テストが緑、`laravelcollective/html` と `fideloper/proxy` が依存から消え、Form シムで画面が描画できる。

## Phase 3: Laravel 10 移行 (3-5日)

- [x] `laravel/framework` を 10.x に更新 (**Laravel 10 の最低 PHP は 8.1**。ここでは PHP 8.2 を使用)
- [x] `docker/php/Dockerfile` を `php:8.2-apache-bookworm` に変更
- [x] 非推奨 API を置換 (Monolog 3 の `LogRecord` 対応など)
- [x] `fzaninotto/faker` → `fakerphp/faker` (Docker 上で先行実施済み)
- [x] `nunomaduro/collision` **^6 → ^7**
- [x] `phpunit/phpunit` **^9 → ^10**、`phpunit.xml` を PHPUnit 10 スキーマへ更新
- [x] `doctrine/dbal` は引き続き ^3.0 (Laravel 11 で不要になるまで維持)
- [x] Mix のビルド確認 (Laravel Mix 6 / Node 16 で成功)
- [x] 認可・ファイル UP/DL の Feature テストを追加 (セキュリティテスト16件)
- [x] G4 (Laravel 10 ゲート) を通過 (2026-10-31 pass)

**完了条件:** PHP 8.2 上で全テストが緑、アップロード/ダウンロードの検証済み。CSV DL と WP 連携モックは Phase 4 で追加する。

## Phase 4: Laravel 11 → 12 移行 (8-12日) — 最大障壁

- [x] `laravel/framework` を 12.x に更新 (11 は EOL のため直接 12 へ)
- [x] **アプリ構造の全面変更:**
  - `bootstrap/app.php` へ統合 (Http Kernel / Console Kernel / RouteServiceProvider / Exception Handler を解体)
  - カスタム Middleware を `bootstrap/app.php` の alias (`guest` / `my-common`) へ移行
  - `routes/console.php` を使用 (スケジュールなし)
  - `withExceptions` / `withMiddleware` コールバックへ移行
- [x] **routes/*.php を全数クラス記法化**
- [x] `doctrine/dbal` を削除 (Laravel 11+ はネイティブ schema modification)
- [x] `nunomaduro/collision` **^7 → ^8**
- [x] `phpunit/phpunit` **^10 → ^11**
- [x] `docker/php/Dockerfile` を `php:8.3-apache-bookworm` に変更
- [x] `config` を Laravel 12 標準構成に整理 (`config/mail.php`, providers の `bootstrap/providers.php` 移行)
- [x] 全テスト・全画面・G5 (Laravel 12 ゲート) を通過 (2026-10-31 pass)

**完了条件:** Laravel 12 + PHP 8.3 で全テストが緑。`laravelcollective/html` / `laravel/helpers` / `fideloper/proxy` / `doctrine/dbal` が依存から消えている。

## Phase 5: Laravel 13 (最終到達点) (2-3日)

- [x] `laravel/framework` を 13.x に更新 (PHP 8.3 以上が必須)
- [x] `docker/php/Dockerfile` を `php:8.4-apache-bookworm` に変更 (PHP 8.5 は追加検証後に判断)
- [x] `symfony/polyfill-php85` とグローバルヘルパーの衝突を確認 (`laravel/helpers` は削除済み)
- [x] PHP 8.4/8.5 の deprecation を完全解消 (implicit nullable 等)
- [x] `config:cache` / `route:cache` / `view:cache` 状態で本番相当起動
- [x] コード内 `env()` の残存ゼロを確認
- [x] G6 (最終ゲート) を通過 (2026-10-31 pass)

**完了条件:** Laravel 13 / PHP 8.4 で `php artisan test` 全件緑、`config:cache` 状態で起動し、主要画面スモークテストがパス。監査勧告ゼロ。

## テスト整備

- [x] Phase 0: テスト DB を MySQL 8 に分離し、ハーネスを構築
- [x] Phase 1-2: PHPUnit ^9 を維持しつつ Feature テストを追加
- [x] Phase 3: PHPUnit ^10 (phpunit.xml 刷新)
- [x] Phase 4: PHPUnit ^11
- [ ] 対象: 認可 (Policy) / CRUD / ファイル UP・DL / CSV / WP 連携 (HTTP モック)
- [ ] 認可マトリクステスト (admin / 組織A / 組織B / ゲスト) をハーネスに組み込む
- [x] CI またはローカルゲートで `php artisan test` を強制

## フロントエンド (本プラン外、別途)

- [ ] Node 16 → **Node 24 LTS**
- [ ] Laravel Mix 6 → Vite 移行
- [ ] Vue 2 → Vue 3 移行 (Vue 2 は 2023-12 EOL)
- [ ] `chart.js` v2 → v4、`leaflet` 1.5 → 最新、axios 更新

## リスクと対策

| リスク | 対策 |
|---|---|
| Docker が php:7.4 / node:16 と古い | 各フェーズの Dockerfile 更新を完了条件に含める |
| `laravelcollective/html` が Laravel 10 非対応 | Phase 2 で解体し、Phase 3 前に依存削除 |
| vendor と composer.lock の不整合 | Phase 0 で `composer validate --strict` と `composer install` を実行 |
| `env()` が config キャッシュで null になる | Phase 0.5 で全箇所を `config()` 化し、Phase 5 でキャッシュ起動確認 |
| テスト DB を SQLite にすると MySQL 固有 SQL が落ちる | テストも MySQL 8 を使用 |
| 認可追加で既存画面が 403 になる | 認可マトリクステストとロール別スモークテストで検証 |
| セキュリティ修正と移行リファクタが混ざる | Phase 0.5 で分離し、タグ `migration/G1` を打つ |
| EOL バージョンに滞留する | 各フェーズのゲート通過後すぐ次へ進む |
| PHPUnit の API 廃止 | Phase 3 で phpunit.xml を刷新 |
| WP 連携が curl 直書きでテスト不能 | Phase 0.5 で Laravel HTTP クライアント化しモック可能にする |

## 完了条件 (全体)

- Docker 内 `php artisan test` 全件パス (Laravel 13 / PHP 8.4)
- G0〜G6 の全ゲート通過と証跡 (`harness/reports/`) のコミット
- 主要画面スモークテストがパス (ログイン / 日報 / 計画 / お知らせ / CSV DL / ファイル UP・DL / WP 連携 / 管理)
- `APP_DEBUG=false` + `config:cache` で本番相当起動
- Phase 0.5 のセキュリティ修正が全て適用され、認可マトリクステストが緑
- `composer audit` で Critical/High がゼロ
- `laravel/helpers` / `laravelcollective/html` / `fideloper/proxy` / `fzaninotto/faker` / `doctrine/dbal` が依存から消えている
