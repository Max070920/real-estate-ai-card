# 不動産AI名刺システム - プロジェクト概要

## 完成した機能

### バックエンド

#### 1. データベーススキーマ ✅
- ユーザーテーブル（新規/既存/無料の区別）
- ビジネスカードテーブル（全17項目対応）
- 挨拶文テーブル（最大5つ、順序変更可能）
- テックツール選択テーブル
- コミュニケーション方法テーブル
- 決済テーブル（Stripe統合準備完了）
- サブスクリプションテーブル
- アクセスログテーブル
- 管理者テーブル（Admin/Client権限）
- 設定テーブル

#### 2. APIエンドポイント ✅

**認証関連**
- `POST /backend/api/auth/register.php` - ユーザー登録
- `POST /backend/api/auth/login.php` - ログイン
- `POST /backend/api/auth/logout.php` - ログアウト
- `GET /backend/api/auth/verify.php` - メール認証

**ビジネスカード関連**
- `GET /backend/api/business-card/get.php` - 取得
- `POST /backend/api/business-card/update.php` - 更新（自動保存対応）
- `GET /backend/api/business-card/public.php` - 公開表示
- `POST /backend/api/business-card/upload.php` - ファイルアップロード

**テックツール**
- `POST /backend/api/tech-tools/generate-urls.php` - URL生成

**決済処理**
- `POST /backend/api/payment/create-intent.php` - 決済意図作成
- `POST /backend/api/payment/webhook.php` - Stripe Webhook

**QRコード**
- `POST /backend/api/qr-code/generate.php` - QRコード生成

**管理画面**
- `POST /backend/api/admin/login.php` - 管理者ログイン
- `GET /backend/api/admin/users.php` - ユーザー一覧（検索・ソート・ページネーション）
- `GET /backend/api/admin/export-csv.php` - CSV出力

#### 3. 共通機能 ✅
- 画像リサイズ機能
- ファイルアップロード処理
- バリデーション関数
- セッション管理
- エラーハンドリング

### フロントエンド

#### 1. ランディングページ ✅
- 新規ユーザー向けLP (`frontend/index.php`)
- 機能紹介セクション
- 使い方セクション（4ステップ）
- 料金プラン表示
- レスポンシブ対応

#### 2. 登録フォーム ✅
- マルチステップフォーム（4ステップ）
  - Step 1: アカウント作成
  - Step 2: 個人情報入力
  - Step 3: テックツール選択
  - Step 4: 確認・決済
- 新規/既存/無料の3パターン対応
- リアルタイムバリデーション
- ファイルアップロード機能

#### 3. 公開名刺表示 ✅
- QRコード経由でアクセス可能
- 名刺情報表示
- 挨拶文表示
- テックツールバナー表示
- コミュニケーション方法表示
- アクセスログ自動記録

#### 4. 編集画面 ✅
- 基本情報編集
- 挨拶文編集（追加・削除・順序変更）
- テックツール選択
- コミュニケーション方法設定
- リアルタイムプレビュー

#### 5. 管理画面 ✅
- 管理者ログイン
- ユーザー一覧表示
- 検索・フィルター機能
- ソート機能
- 入金確認・QRコード発行
- CSV出力機能
- アクセス統計表示

## 実装済み要件

### ✅ 必須機能
1. **名刺部入力項目（17項目）**
   - 会社名、ロゴ、顔写真
   - 宅建業者番号（プルダウン対応）
   - 郵便番号自動入力対応（実装準備済み）
   - 個人情報（名前、役職、資格など）
   - フリー入力欄（文字・画像・バナー対応）

2. **挨拶文機能**
   - タイトル・本文のセットを5つまで
   - 順序変更可能
   - デフォルト文章5セット準備

3. **テックツール部**
   - 7種類のツール選択可能
   - 最低2つ以上選択必須
   - URL自動生成（新規: 6桁連番、既存: 手入力）

4. **コミュニケーション機能部**
   - LINE、Messenger、WhatsApp等
   - SNS連携（Instagram、Facebook、X等）
   - チェックボックスで表示/非表示

5. **決済システム**
   - 新規: 初期30,000円 + 月額500円
   - 既存: 初期20,000円のみ
   - 無料版: 決済不要
   - Stripe統合準備完了
   - 銀行振込対応準備済み

6. **QRコード生成**
   - 決済完了後に自動発行
   - 管理画面から手動発行可能
   - URL: `https://www.ai-fcard.com/{slug}`

7. **管理画面**
   - Admin/Client権限分け
   - ユーザー一覧・検索・ソート
   - 入金確認・QRコード発行
   - アクセス統計表示
   - CSV出力

## 追加実装が必要な機能

### ⚠️ 実装準備済み（コメントアウト済み）
1. **OCR機能** - 名刺読み取り自動入力
2. **メール送信機能** - 認証メール、通知メール
3. **郵便番号API連携** - 住所自動入力
4. **Stripe実装** - SDK統合が必要
5. **QRコードライブラリ** - Composerでインストール必要

### 📝 今後実装推奨
1. **パスワードリセット機能**
2. **定期課金管理**（サブスクリプション）
3. **動画アップロード機能**
4. **多言語対応**
5. **テンプレート選択機能**

## ファイル構造

```
.
├── backend/
│   ├── api/                  # APIエンドポイント
│   │   ├── auth/            # 認証
│   │   ├── business-card/   # ビジネスカード
│   │   ├── admin/           # 管理画面
│   │   ├── payment/         # 決済
│   │   ├── qr-code/         # QRコード
│   │   └── tech-tools/      # テックツール
│   ├── config/              # 設定ファイル
│   ├── database/            # データベーススキーマ
│   ├── includes/            # 共通関数
│   └── uploads/             # アップロードファイル
├── frontend/
│   ├── admin/               # 管理画面
│   ├── assets/              # CSS, JS, 画像
│   ├── index.php            # ランディングページ
│   ├── register.php         # 登録フォーム
│   ├── card.php             # 公開名刺表示
│   └── edit.php             # 編集画面
├── .htaccess                # Apache設定
├── README.md                # 基本ドキュメント
├── SETUP.md                 # セットアップガイド
└── PROJECT_SUMMARY.md       # このファイル
```

## 次のステップ

1. **環境セットアップ**
   - データベース作成
   - Composer依存関係インストール
   - 設定ファイル編集

2. **機能テスト**
   - ユーザー登録フロー
   - ビジネスカード作成
   - 決済処理
   - 管理画面操作

3. **本番環境準備**
   - Stripe APIキー設定
   - メール送信機能実装
   - SSL証明書設定
   - ドメイン設定

4. **追加機能実装**
   - OCR機能
   - 郵便番号API連携
   - 動画アップロード

## 技術スタック

- **Backend**: PHP 7.4+, MySQL
- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **Payment**: Stripe API
- **QR Code**: endroid/qr-code or phpqrcode
- **Server**: Apache/Nginx

## 注意事項

1. **セキュリティ**
   - 本番環境では必ず環境変数を使用
   - パスワードはハッシュ化済み
   - SQLインジェクション対策済み（Prepared Statements）
   - XSS対策済み（htmlspecialchars）

2. **パフォーマンス**
   - 画像リサイズ機能実装済み
   - データベースインデックス設定済み
   - ページネーション対応

3. **拡張性**
   - モジュール化された構造
   - APIベースの設計
   - 設定ファイルによる柔軟な変更

