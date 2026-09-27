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

DB データは名前付きボリューム `db-data` に保持される。DBを含めて初期化する必要がある場合だけ、`docker compose down -v` を実行してから初回手順をやり直すこと。この操作はローカル開発DBを削除する。
