# 災図（saiz）
被災地情報共有支援システム saizのリポジトリです。

## インストール手順

1. リポジトリをcloneする。

2. composer installを実行する。
前提としてサーバーにcomposerがインストールされていることを確認する。

インストールされていない場合は以下を実行しインストールする。
推奨する配置場所はリポジトリの1つ上の階層である。

`curl -sS https://getcomposer.org/installer | php`

リポジトリの直下のディレクトリに移動し、以下を実行する。

`../composer.phar install` 

(composer.pharのパスは環境によって読み替える。)

3. envファイルを作成する。
.env.exampleファイルをコピーし、.envファイルを作成する。  
`cp .env.example .env`

作成後に以下を実行する。

`php artisan key:generate`

4. envファイルのDB設定を実際のDB接続情報に変更する。

```angular2html
DB_CONNECTION=
DB_HOST=
DB_PORT=
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=
```

5. envファイルのSSL設定を環境に合わせて変更する。  
値：true | false

```
SSL_USE=true
```

6. storageフォルダのパーミッションを777に変更する。

```
chmod -R 777 storage
```

7. DBのマイグレーションの実行  
以下を実行し、DBにテーブルを作成する。
```
php artisan migrate
```

8. 初期データの登録  
`DevDataSeeder` を実行する前に、`.env` の `DEV_ADMIN_PASSWORD` に任意のパスワードを設定してください。  
以下を実行し初期データを登録する。
```
php artisan db:seed
php artisan db:seed --class=DevDataSeeder
```

9. DocumentRootへの配置

以下のファイルのシンボリックリンクをDocumentRootに貼る。  
これでブラウザからのアクセスが可能になる。

```
public/index.php
public/dist
public/img
public/manual
public/plugins
public/.htaccess
public/robots.txt
```


以上。

## Docker による開発環境

Docker Desktop または Docker Engine と Docker Compose v2 があれば、ホストへ PHP、Composer、MySQL、Node.js を直接インストールせずに開発できる。

初回のみ、以下を順に実行する。

```bash
cp .env.docker.example .env
docker compose build
docker compose up -d db app
docker compose run --rm app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed
# 開発用のサンプルデータも必要な場合のみ実行
docker compose exec app php artisan db:seed --class=DevDataSeeder
docker compose up -d node
```

アプリケーションは http://localhost:7998 、MySQL はホストの `localhost:33060`（DB名・ユーザー・パスワードはすべて `saiz`、root パスワードは `root`）で利用できる。これらのDB認証情報はローカル開発専用であり、本番環境には使用しないこと。

日常的な操作例は次のとおり。

```bash
# 起動・停止
docker compose up -d
docker compose down

# Laravel コマンドとテスト
docker compose exec app php artisan migrate
docker compose exec app php artisan test

# フロントエンドの監視ビルド（初回は npm ci を自動実行）
docker compose up node

# コンテナ内シェル
docker compose exec app bash
```

## 学区レイヤーデータの取込

災害詳細の地図に表示する小学校区・中学校区は、国土数値情報 A27/A32（2023年度）を都道府県単位で取り込む。災害情報で使用する都道府県ごとに実行する。

```bash
# 例: 東京都
docker compose exec app php artisan school-districts:import 13

# 再取込
docker compose exec app php artisan school-districts:import 13 --force
```

取込データは `storage/app/school-districts/` に保存される。地図の学区レイヤーは、災害に登録された市町村の行政区域コードを使って必要なファイルだけを読み込む。

MLITへ接続できない環境では、A27/A32のZIPを `A27-23_13_GML.zip`、`A32-23_13_GML.zip` の形式でディレクトリへ配置し、`--source-dir` を指定する。

```bash
docker compose exec app php artisan school-districts:import 13 \
  --source-dir=/var/www/html/storage/app/import-school-districts/source
```

出典: 国土数値情報（小学校区A27・中学校区A32、国土交通省）。ライセンスは CC BY 4.0（一部自治体は利用条件あり）。学区データは参考情報であり、公式な通学区域を示すものではない。

## 合併前行政界データの取込

災害詳細の「合併前行政界」レイヤーは、歴史的行政区域データセットβ版（CODH / Geoshape）の都道府県別TopoJSONをDBに取り込んで表示する。富山県・石川県は1920-01-01〜2023-01-01の80時点を収録済み。

```bash
# 富山県・石川県
docker compose exec app php artisan historical-boundaries:import 16
docker compose exec app php artisan historical-boundaries:import 17

# 再取込・解像度変更
docker compose exec app php artisan historical-boundaries:import 16 --force --resolution=h
```

取込データは `historical_boundaries` テーブルに保存される。出典は『歴史的行政区域データセットβ版』（CODH作成、doi:10.20676/00000447）および国土数値情報（行政区域N03）。データはβ版の参考情報であり、公式な行政界を示すものではない。

DB データは名前付きボリューム `db-data` に保持される。DBを含めて初期化する必要がある場合だけ、`docker compose down -v` を実行してから初回手順をやり直すこと。この操作はローカル開発DBを削除する。

## WSL2 + Docker Engine（移行後）

このプロジェクトは WSL2 Ubuntu の `/home/saiz/work/saiz` へ移行済み。Windowsからは `\\wsl$\Ubuntu\home\saiz\work\saiz` で参照できる。**移行前のWindows側パスは編集に使用しないこと。**

Docker Desktop ではなく、Ubuntu内の Docker Engine と Docker Compose を使用する。

```bash
# 起動（Dockerサービスはsystemdで自動起動）
wsl -d Ubuntu -u saiz -- bash -lc "cd ~/work/saiz && docker compose up -d db db-test app"

# Dockerが停止している場合
wsl -d Ubuntu -u root -- systemctl start docker

# テスト
wsl -d Ubuntu -u saiz -- bash -lc "cd ~/work/saiz && docker compose exec -T app php artisan test"
```

テスト用DBは `db-test` サービス（ホスト側ポート33061）で提供する。DB・アプリのデータはWSL内のDockerボリュームに保持される。

WSL2のアイドル自動停止でコンテナが止まらないよう、Windows側の `%USERPROFILE%\.wslconfig` に以下を設定する。

```ini
[general]
instanceIdleTimeout=-1

[wsl2]
vmIdleTimeout=-1
```

`app` / `db` / `db-test` は `restart: unless-stopped` で自動再起動する。
