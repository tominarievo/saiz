# 移行実行計画 (Execution Plan)

対象: `migration-plan.md` (v3) を実行可能なタスク・日程・ゲートに落とし込んだもの  
期間: 2026-10-27 〜 2027-01-09 (約10週間、バッファ込み)  
関連: `migration-harness.md`

## 1. 目的と成功条件

### 目的

- Laravel 8.83.3 / PHP 7.4 を **Laravel 13 / PHP 8.4** へ移行する
- 重大なセキュリティ脆弱性 (無認可 CRUD / IDOR / 未認証 DL / XSS / 未検証アップロード) を解消する
- 各フェーズを再現可能に検証するハーネスとゲートを整備する

### 成功条件 (Definition of Success)

1. G0〜G6 の全ゲートが緑で、証跡が `harness/reports/` に残っている
2. Laravel 13 / PHP 8.4 で `php artisan test` が全件パス
3. 認可マトリクステストとセキュリティテストが緑
4. `config:cache` / `route:cache` / `view:cache` 状態で本番相当起動
5. `composer audit` の Critical/High がゼロ
6. 主要画面のスモークテストがパス

## 2. 前提と制約

- 開発・検証はすべて Docker 内で行う (ホスト PHP 8.5 は使わない)
- テスト DB は **MySQL 8** (`saiz_test`)。SQLite は MySQL 固有 SQL のため不可
- 現行 Docker は `php:8.1-apache-bookworm` / `node:16-bullseye`。各フェーズで更新する
- フロントエンド (Vue 2 → 3 / Mix → Vite) は別プロジェクト。本計画では Node 24 への更新とビルド確認まで
- 本番 DB のデータ移行はスキーマ互換のため原則不要。ただしバックアップと復元手順を必須とする
- 外部 WordPress 連携は Phase 0.5 で HTTP クライアント化し、テストではモックする

## 3. 体制と役割 (RACI)

| 役割 | 担当 | 主な責務 |
|---|---|---|
| 技術リード (TL) | 1名 | 全体設計、ゲート判定、リスク管理、レビュー |
| バックエンド (BE) | 2名 | Laravel/PHP 移行、認可・セキュリティ修正、テスト |
| フロントエンド (FE) | 1名 | Blade `Form::` 解体、ビルド、画面スモーク |
| QA | 1名 | 認可マトリクス、回帰、スモーク、証跡確認 |
| インフラ (Infra) | 0.5名 | Docker マトリクス、CI、DB バックアップ |

RACI の目安:
- 移行実装: BE = R、TL = A、FE/QA = C、Infra = C
- ハーネス/CI: Infra = R、TL = A、BE/QA = C
- セキュリティ修正: BE = R、TL = A、QA = C
- ゲート判定: TL = A、QA = R、BE/FE/Infra = C

## 4. ワークストリーム

| ID | ワークストリーム | 内容 | 主担当 |
|---|---|---|---|
| WS-A | Security | 認可・IDOR・XSS・ファイル・env() の修正 | BE/QA |
| WS-B | Runtime | PHP 8.1→8.4、Docker、依存更新 | BE/Infra |
| WS-C | Framework | Laravel 8→9→10→12→13、構造移行 | BE |
| WS-D | Blade/UI | `laravelcollective/html` 解体、画面回帰 | FE |
| WS-E | Harness/QA | テスト、認可マトリクス、CI、証跡 | QA/Infra |
| WS-F | Ops | DB バックアップ、リリース、ロールバック | Infra/TL |

## 5. スケジュール (10週間)

| 週 | 期間 | フェーズ | 主な成果物 | ゲート |
|---|---|---|---|---|
| 1 | 10/27-10/31 | Phase 0 | vendor 同期、ハーネス、テストDB、ベースライン | **G0** |
| 2 | 11/02-11/06 | Phase 0.5 | セキュリティ修正、認可マトリクス、セキュリティテスト | **G1** |
| 3 | 11/09-11/13 | Phase 1 | PHP 8.1、str_slug、nullable、依存調整 | **G2** |
| 4-5 | 11/16-11/27 | Phase 2 | Laravel 9、`Form::` 解体、依存更新 | **G3** |
| 6 | 11/30-12/04 | Phase 3 | Laravel 10、PHPUnit 10、Faker 移行 | **G4** |
| 7-8 | 12/07-12/18 | Phase 4 | Laravel 11→12、構造移行、DBAL 削除 | **G5** |
| 9 | 12/21-12/25 | Phase 5 | Laravel 13、PHP 8.4、キャッシュ起動 | **G6** |
| 10 | 12/28-01/09 | バッファ/リリース | 回帰、リリース判定、フロント引き継ぎ | リリース |

> 年末年始の休暇を考慮し、Week 10 はバッファとする。Phase 2 と Phase 4 は超過リスクが高いため、週次で進捗を再評価する。

## 6. タスク分解

> 工数は**人日**。BE 2名・FE 1名・QA 1名で並行するため、経過日数はこれより短くなる。クリティカルパスは Phase 2 の Blade 解体と Phase 4 の構造移行。

### Phase 0: 準備 (G0)

| ID | タスク | 工数 | 依存 | 成果物 |
|---|---|---|---|---|
| P0-01 | `composer validate --strict` / `composer install` で vendor を lock に同期 | 0.5d | - | 同期済み vendor、実行ログ |
| P0-02 | Docker ハーネス (PHP/NODE バージョン可変) を構築 | 1.5d | P0-01 | `harness/docker/`、`compose.harness.yml` |
| P0-03 | テスト DB (`saiz_test`) と `phpunit.xml` を設定 | 0.5d | P0-02 | テスト設定、DB 初期化スクリプト |
| P0-04 | ベースライン取得 (`--version`, `route:list`, `test`, `composer audit`) | 0.5d | P0-03 | `harness/reports/G0/` |
| P0-05 | ブランチ・タグ規約、バックアップ/復元手順の確定 | 0.5d | - | 運用ドキュメント |
| P0-06 | `minimum-stability: dev` 削除、`composer update --lock` で content-hash 更新、不要依存の棚卸し | 0.5d | P0-01 | 更新済み composer.json / composer.lock |
| P0-07 | G0 判定 | 0.5d | P0-01〜06 | G0 レポート |

### Phase 0.5: セキュリティ (G1)

| ID | タスク | 工数 | 依存 | 成果物 |
|---|---|---|---|---|
| S-01 | `AdminUserController` の管理者認可 | 0.5d | G0 | Policy/Middleware、テスト |
| S-02 | 各 CRUD の認可 (Shelter/Organization/Disaster/SupportCategory/User/OrganizationSeed/Information/Report::destroy) | 2.0d | S-01 | 認可コード、認可マトリクステスト |
| S-03 | `PlanCommentController` の store/delete 認可 | 0.5d | S-02 | テスト |
| S-04 | `frontFileDownload` の認証 + path traversal 対策 | 0.5d | G0 | テスト (正常/攻撃) |
| S-05 | アップロード検証 (Wysiwyg/Information) | 0.5d | G0 | バリデーション、テスト |
| S-06 | `Information::getEditedContent()` のサニタイズ | 0.5d | G0 | サニタイザ、XSS テスト |
| S-07 | `file_data` のサーバー側バリデーション | 0.5d | G0 | バリデーション、テスト |
| S-08 | `env()` → `config()` 全箇所 + WP HTTP クライアント化 | 1.0d | G0 | config、クライアント、モックテスト |
| S-09 | `UploadImageFileRequest` 欠落の修正 / 未使用コード削除 | 0.25d | G0 | コード |
| S-10 | `APP_DEBUG=false` 等の設定整理 | 0.25d | G0 | `.env.example` |
| S-11 | G1 判定 | 0.5d | S-01〜10 | G1 レポート |

### Phase 1: PHP 8.1 (G2)

| ID | タスク | 工数 | 依存 | 成果物 |
|---|---|---|---|---|
| P1-01 | Docker を `php:8.1-apache` に更新 | 0.5d | G1 | Dockerfile |
| P1-02 | `str_slug` 2箇所を `Str::slug` に置換 | 0.25d | P1-01 | コード |
| P1-03 | implicit nullable の修正 | 0.5d | P1-01 | コード |
| P1-04 | `laravel/helpers` 削除、Carbon 依存更新 | 0.5d | P1-02 | composer |
| P1-05 | 回帰テスト + G2 | 0.5d | P1-01〜04 | G2 レポート |

### Phase 2: Laravel 9 + Blade 解体 (G3)

| ID | タスク | 工数 | 依存 | 成果物 |
|---|---|---|---|---|
| P2-01 | Laravel 9 へ更新、RouteServiceProvider 対応 | 1.0d | G2 | composer、コード |
| P2-02 | 依存更新 (flysystem v3, dbal ^3, collision ^6, proxy 削除, ui は未使用確認のうえ削除) | 1.0d | P2-01 | composer |
| P2-03 | ルートのクラス記法化 (第一弾) | 1.0d | P2-01 | routes |
| P2-04 | `Form::` / `HTML::` 解体 (画面群 A: お知らせ/管理/認証) | 1.5d | P2-01 | blade、PR |
| P2-05 | `Form::` / `HTML::` 解体 (画面群 B: 日報/計画/支援先) | 2.0d | P2-04 | blade、PR |
| P2-06 | `Form::` / `HTML::` 解体 (画面群 C: 組織/カテゴリ/その他) | 1.5d | P2-04 | blade、PR |
| P2-07 | 残存ゼロ確認、`laravelcollective/html` 削除 | 0.5d | P2-04〜06 | composer |
| P2-08 | 回帰・スモーク + G3 | 1.0d | P2-01〜07 | G3 レポート |

### Phase 3: Laravel 10 (G4)

| ID | タスク | 工数 | 依存 | 成果物 |
|---|---|---|---|---|
| P3-01 | Laravel 10 へ更新、Docker 8.2 | 1.0d | G3 | composer、Docker |
| P3-02 | 非推奨 API 置換 | 1.0d | P3-01 | コード |
| P3-03 | Faker 移行、PHPUnit 10、phpunit.xml 刷新、collision ^7 | 1.0d | P3-01 | composer、設定 |
| P3-04 | 認可/ファイルの Feature テスト追加 | 1.0d | P3-01 | tests |
| P3-05 | 回帰 + G4 | 0.5d | P3-01〜04 | G4 レポート |

### Phase 4: Laravel 11 → 12 (G5)

| ID | タスク | 工数 | 依存 | 成果物 |
|---|---|---|---|---|
| P4-01 | Laravel 11 へ更新、`bootstrap/app.php` へ構造移行 | 2.0d | G4 | bootstrap、コード |
| P4-02 | Kernel/RouteServiceProvider/Exception Handler/Middleware 解体 | 2.0d | P4-01 | コード |
| P4-03 | ルート全数クラス記法化、スケジュール移行 | 1.0d | P4-01 | routes |
| P4-04 | Laravel 12 へ更新、Docker 8.3 | 1.0d | P4-01〜03 | composer |
| P4-05 | `doctrine/dbal` 削除、collision ^8、PHPUnit ^11 | 0.5d | P4-04 | composer |
| P4-06 | config 整理、全テスト・全画面回帰 | 2.0d | P4-04 | 設定、レポート |
| P4-07 | G5 判定 | 0.5d | P4-01〜06 | G5 レポート |

### Phase 5: Laravel 13 (G6)

| ID | タスク | 工数 | 依存 | 成果物 |
|---|---|---|---|---|
| P5-01 | Laravel 13 へ更新、Docker PHP 8.4 | 1.0d | G5 | composer、Docker |
| P5-02 | deprecation 解消、polyfill 衝突確認 | 0.5d | P5-01 | コード |
| P5-03 | `config:cache` / `route:cache` / `view:cache` 起動検証 | 0.5d | P5-01 | レポート |
| P5-04 | 最終回帰・スモーク・G6 | 1.0d | P5-01〜03 | G6 レポート |

## 7. ゲート定義 (Exit Criteria)

| ゲート | 対象 | 必須条件 |
|---|---|---|
| **G0** | Phase 0 | vendor が lock と一致 / テスト DB が MySQL / `php artisan test` が現状緑 / ベースライン証跡あり |
| **G1** | Phase 0.5 | 認可マトリクス緑 / セキュリティテスト緑 / `composer audit` Critical/High ゼロ / 主要画面回帰緑 |
| **G2** | Phase 1 | PHP 8.1 でテスト・スモーク緑 / `str_slug` 0件 / implicit nullable 警告 0件 |
| **G3** | Phase 2 | Laravel 9 で全テスト緑 / `Form::`・`HTML::` 0件 / collective・proxy 削除済み / flysystem v3 |
| **G4** | Phase 3 | PHP 8.2 / Laravel 10 で全テスト緑 / PHPUnit 10 / File・CSV・WP モック緑 |
| **G5** | Phase 4 | Laravel 12 / PHP 8.3 / 全テスト緑 / 旧 Kernel 系撤去 / dbal 削除 / 構造移行済み |
| **G6** | Phase 5 | Laravel 13 / PHP 8.4 / 全テスト緑 / `config:cache` 起動 / 主要画面スモーク緑 |

各ゲートの詳細な自動チェックは `migration-harness.md` を参照。

## 8. マイルストーンとタグ

| マイルストーン | タグ | 内容 |
|---|---|---|
| M0 | `migration/G0` | ハーネス構築完了、ベースライン確定 |
| M1 | `migration/G1` | セキュリティ修正完了 |
| M2 | `migration/G2` | PHP 8.1 対応完了 |
| M3 | `migration/G3` | Laravel 9 + Blade 解体完了 |
| M4 | `migration/G4` | Laravel 10 対応完了 |
| M5 | `migration/G5` | Laravel 12 対応完了 |
| M6 | `migration/G6` | Laravel 13 対応完了 (リリース候補) |

タグはゲート通過時のみ打つ。タグにはゲートレポート (`harness/reports/Gx/`) を必ず含める。

## 9. リスク管理

| ID | リスク | 確率 | 影響 | 対策 | トリガー |
|---|---|---|---|---|---|
| R1 | `Form::` 解体が想定より大きい | 高 | 高 | 画面群でPR分割、FE を Phase 2 に集中投下 | P2-04 が 2日超過 |
| R2 | Laravel 11/12 の構造移行で画面が壊れる | 中 | 高 | ハーネス G5 と画面スモーク、早期に P4-01 着手 | ルート/画面の失敗が 5 件超 |
| R3 | 認可追加で既存業務が 403 | 中 | 中 | 認可マトリクス、ロール別テストデータ | スモークで 403 多発 |
| R4 | vendor/lock 不整合で環境が再現しない | 中 | 中 | Phase 0 で同期、Docker 固定 | `composer install` 失敗 |
| R5 | テスト DB が本番と差異 | 中 | 中 | MySQL 8 固定、`migrate:fresh --seed` | SQL 差異によるテスト失敗 |
| R6 | 外部 WP 連携がテストできない | 高 | 中 | HTTP クライアント化 + モック | テストで外部通信が発生 |
| R7 | 年末年始で体制が薄い | 中 | 中 | Week 10 をバッファ、G5 を 12/18 まで | 12月の進捗遅延 |
| R8 | セキュリティ修正の回帰 | 中 | 高 | G1 で認可・XSS・ファイルの自動テスト | セキュリティテスト失敗 |

## 10. 運用ルール

- 日次: 15分のスタンドアップ (前日/今日/ブロッカー)
- 週次: ゲート進捗レビュー、リスク更新、翌週計画
- フェーズ完了時: ゲート判定会 (TL/QA/BE/FE/Infra)、タグ付け、レポート保存
- 変更管理: 目標バージョン・テストDB・WP クライアント化・collective 解体時期は変更管理対象。変更時は本計画と `migration-plan.md` を更新
- ブランチ: `hotfix/security`, `migration/php81`, `migration/laravel9`, `migration/laravel10`, `migration/laravel12`, `migration/laravel13`
- コミット: 各ゲートで `composer.lock` を含めてタグ付け。ロールバックは前タグ + DB バックアップで実施

## 11. Definition of Done (各タスク)

- コード変更に対するテストがある (またはハーネスのスモークで担保)
- `php artisan test` が緑
- 静的解析 (Larastan) とフォーマット (Pint) が緑
- ゲートレポートに実行コマンドと結果が残っている
- レビュー済み (TL または指定レビュアー)
- ドキュメント (本計画/ハーネス) の更新が必要なら反映済み

## 12. 成果物一覧

- 移行済みアプリケーション (Laravel 13 / PHP 8.4)
- セキュリティ修正一式と認可マトリクステスト
- ハーネス (`harness/`) と CI ワークフロー
- ゲートレポート G0〜G6 (`harness/reports/`)
- 更新済み `migration-plan.md` / `migration-execution-plan.md` / `migration-harness.md`
- リリース手順・ロールバック手順

## 13. 実装進捗 (2026-10-31)

- [x] ブランチ `migration/phase0` を作成
- [x] ハーネス雛形を作成 (`harness/docker`, `harness/scripts`, `harness/wp-stub`, `harness/reports`)
- [x] CI ワークフロー雛形を作成 (`.github/workflows/migration.yml`)
- [x] テスト DB を MySQL 8 (`saiz_test`) に設定 (`phpunit.xml`, `compose.harness.yml`)
- [x] `composer.json` から `minimum-stability: dev` を削除
- [x] シェルスクリプトの LF 固定 (`.gitattributes`)
- [x] `docker/php/Dockerfile` を `php:8.1-apache-bookworm` に更新 (bullseye の apt 404 対策)
- [x] 暫定ローカル起動 (ホスト PHP 8.1 + MySQL コンテナ) でログイン→ホームまで確認
- [x] Docker イメージをビルド (`saiz-app`) し、db + app を起動
- [x] Docker 上でログイン→ホームまで確認 (GET /login 200 / POST 302 / GET /home 200)
- [x] `composer.lock` の content-hash を同期し、`fzaninotto/faker` を `fakerphp/faker` に置換
- [x] `PlanController` に存在しない index/show を resource ルートから除外 (`/plans` 500 の解消)
- [x] ハーネス G0 を実行し、レポートを保存 (status=pass)

### Phase 0.5 / G1 (セキュリティ)

- [x] `AdminUserController` / `DisasterController` / `SupportCategory1·2Controller` を管理者限定化
- [x] `OrganizationController` / `UserController` / `OrganizationSeedController` を組織スコープ化
- [x] `ShelterController` の更新を管理者または紐付け組織に限定、削除を管理者限定化
- [x] `ReportController` の参照・削除を組織ポリシー化、作成/更新時の `organization_id` を強制
- [x] `PlanController` / `PlanCommentController` の組織スコープと権限を修正
- [x] `frontFileDownload` を認証必須化し、許可ディレクトリ + パストラバーサル検証を追加
- [x] `WysiwygFileUploadController` / `UploadImageFileRequest` のアップロード検証を追加
- [x] `Information` の HTML を HTMLPurifier でサニタイズ、`file_data` を JSON 検証 + `@json` 出力に変更
- [x] 実行時 `env()` を `config()` 化 (`wordpress` / `const` / `mail.bcc`)
- [x] セキュリティテスト 16 件 (認可マトリクス / IDOR / ファイル / XSS) を追加
- [x] ルート認可監査 (`route-audit.php`) と Critical/High 監査許可リストを追加
- [x] **G1 を実行し、レポートを保存 (status=pass, PHP 8.1.34 / Laravel 8.83.3)**

### Phase 1 / G2 (PHP 8.1)

- [x] `composer.json` の PHP 制約を `^8.1` に更新し、`composer.lock` を同期
- [x] `str_slug()` を `Str::slug()` に置換 (`config/cache.php`, `config/session.php`)
- [x] `str_random()` を `Str::random()` に置換 (`database/factories/UserFactory.php`)
- [x] `app/Shelter.php` の暗黙 nullable を `?SupportCategory1` に修正
- [x] `laravel/helpers` を依存から削除
- [x] **G2 を実行し、レポートを保存 (status=pass, PHP 8.1.34 / Laravel 8.83.3)**

### Phase 2 / G3 (Laravel 9 + Blade フォーム)

- [x] `laravel/framework` を 9.52.23 に更新 (Symfony 6 / PHPUnit 9.6)
- [x] `doctrine/dbal` ^3.0 / `league/flysystem-aws-s3-v3` ^3.0 / `nunomaduro/collision` ^6.0 に更新
- [x] `fideloper/proxy` を削除し、`TrustProxies` を Laravel 標準へ移行
- [x] `laravelcollective/html` を削除し、`App\Support\Form` の互換シムへ移行
- [x] `laravel/ui` は認証トレイト (`AuthenticatesUsers`) の提供元のため ^4.0 で維持
- [x] G3 用の依存チェック (`dependency-check.sh`) と監査スナップショット fallback を追加
- [x] **G3 を実行し、レポートを保存 (status=pass, PHP 8.1.34 / Laravel 9.52.23)**

### Phase 3 / G4 (Laravel 10 + PHP 8.2)

- [x] `docker/php/Dockerfile` を `php:8.2-apache-bookworm` に更新
- [x] `laravel/framework` を 10.50.4 に更新 (PHPUnit 10.5.66 / collision 7.12)
- [x] `laravel/ui` を削除し、認証トレイト (`AuthenticatesUsers` / `RedirectsUsers` / `ThrottlesLogins`) を `app/Traits` へ移設
- [x] `phpunit.xml` を PHPUnit 10 スキーマへ更新
- [x] Monolog 3 の `LogRecord` に対応 (`CustomizeFormatter`)
- [x] Laravel Mix 6 / Node 16 の本番ビルド成功 (npm audit 74件はフロント別プランで対応)
- [x] **G4 を実行し、レポートを保存 (status=pass, PHP 8.2.35 / Laravel 10.50.4)**

### Phase 4 / G5 (Laravel 12 + PHP 8.3)

- [x] `laravel/framework` を 12.70.2 に更新 (PHPUnit 11.5.57 / collision 8.9.5)
- [x] `bootstrap/app.php` / `bootstrap/providers.php` の新構造へ移行
- [x] `app/Http/Kernel.php` / `app/Console/Kernel.php` / `RouteServiceProvider` / `Exception Handler` を撤去
- [x] `routes/web.php` を全数クラス記法化
- [x] `doctrine/dbal` を削除
- [x] `config/mail.php` を Laravel 12 形式へ更新、providers を `bootstrap/providers.php` へ移行
- [x] `docker/php/Dockerfile` を `php:8.3-apache-bookworm` に更新
- [x] **G5 を実行し、レポートを保存 (status=pass, PHP 8.3.36 / Laravel 12.70.2)**

### Phase 5 / G6 (Laravel 13 + PHP 8.4)

- [x] `laravel/framework` を 13.39.0 に更新 (PHP 8.4.27 / PHPUnit 11.5.57 / collision 8.9.5)
- [x] `docker/php/Dockerfile` を `php:8.4-apache-bookworm` に更新
- [x] Closure ルートをコントローラ化 (`RootController` / `CsrfTokenController` / `Api\UserController`)
- [x] `laravel/tinker` を削除 (Laravel 13 非対応)
- [x] `config:cache` / `route:cache` / `view:cache` の起動検証を追加
- [x] Composer advisory block の一時解除を戻し、監査勧告ゼロを確認
- [x] **G6 を実行し、レポートを保存 (status=pass, PHP 8.4.27 / Laravel 13.39.0)**

> 補足: Docker Desktop (Hyper-V) の初回 bind mount 時にファイル共有の許可ダイアログが出る。`<HOME>\work\saiz` を共有許可済み。
> 補足: G0〜G6 の全ゲートが pass。`harness/reports/G0`〜`G6` に証跡を保存済みです。
> 補足: `laravelcollective/html` は削除済み。Blade の `Form::` は自前シム (`App\Support\Form`) が処理します。
> 次のアクション: リリース判定、フロントエンド (Vue 2 / Mix → Vue 3 / Vite、npm audit)、WP 連携の HTTP クライアント化、Form:: の標準 HTML 化。

