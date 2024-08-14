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
