# セットアップガイド

## 1. 環境準備

### 必要なソフトウェア
- PHP 7.4以上
- MySQL 5.7以上（またはMariaDB 10.3以上）
- Apache 2.4以上（またはNginx）
- Composer

### XAMPP環境でのセットアップ

1. XAMPPをインストール
2. プロジェクトを `C:\xampp\htdocs\php\` に配置

## 2. データベースセットアップ

```bash
# MySQLにログイン
mysql -u root -p

# データベース作成
CREATE DATABASE business_card CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# スキーマのインポート
mysql -u root -p business_card < backend/database/schema.sql
```

またはphpMyAdminを使用:
1. phpMyAdminにアクセス (http://localhost/phpmyadmin)
2. 新しいデータベース `business_card` を作成
3. スキーマファイル (`backend/database/schema.sql`) をインポート

## 3. 設定ファイルの編集

### backend/config/database.php

```php
private $host = 'localhost';
private $db_name = 'business_card';
private $username = 'root';  // 必要に応じて変更
private $password = '';       // 必要に応じて変更
```

### backend/config/config.php

必要な設定を確認・編集:
- `BASE_URL`: プロジェクトのベースURL
- `STRIPE_*`: StripeのAPIキー（本番環境用）

## 4. ディレクトリ・ファイル権限の設定

```bash
# Windowsの場合 (PowerShell管理者として実行)
mkdir backend\uploads\qr_codes
mkdir backend\uploads\photo
mkdir backend\uploads\logo

# Linux/Macの場合
mkdir -p backend/uploads/{qr_codes,photo,logo}
chmod -R 755 backend/uploads
```

## 5. Composer依存関係のインストール

```bash
cd backend
composer install
```

インストールされるパッケージ:
- `stripe/stripe-php`: Stripe決済処理
- `endroid/qr-code`: QRコード生成

## 6. QRコード生成ライブラリの確認

QRコード生成には以下のいずれかが必要です:
- `endroid/qr-code` (Composer経由でインストール)
- または手動で `phpqrcode` をインストール

## 7. 初期管理者アカウント

スキーマに初期管理者アカウントが作成されます:
- メール: `admin@rchukai.jp`
- パスワード: `admin123` (本番環境で必ず変更してください)

## 8. Apache設定（.htaccessが動作しない場合）

`httpd.conf` または `apache2.conf` で以下を確認:

```apache
LoadModule rewrite_module modules/mod_rewrite.so
AllowOverride All
```

## 9. 動作確認

1. ブラウザで http://localhost/php/frontend/index.php にアクセス
2. ランディングページが表示されることを確認
3. 登録フォームにアクセスして動作確認
4. 管理画面にログインして動作確認

## 10. トラブルシューティング

### データベース接続エラー
- `backend/config/database.php` の接続情報を確認
- MySQLサービスが起動しているか確認

### ファイルアップロードエラー
- `backend/uploads/` ディレクトリの権限を確認
- PHPの `upload_max_filesize` と `post_max_size` を確認

### APIエラー
- ブラウザの開発者ツールでエラーメッセージを確認
- `backend/config/config.php` でエラー表示を有効化

### .htaccessが動作しない
- Apacheの `mod_rewrite` が有効か確認
- `AllowOverride All` が設定されているか確認

## 11. 本番環境への移行

1. 環境変数の設定
2. Stripe APIキーの設定
3. メール送信機能の実装
4. HTTPS設定
5. データベースバックアップ設定
6. ログ設定
7. セキュリティ設定の見直し

