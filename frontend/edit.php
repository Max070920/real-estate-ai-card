<?php
/**
 * Business Card Editor Page
 */
require_once __DIR__ . '/../backend/config/config.php';
require_once __DIR__ . '/../backend/includes/functions.php';

startSessionIfNotStarted();

// 認証チェック
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>名刺編集 - 不動産AI名刺</title>
    <link rel="stylesheet" href="assets/css/edit.css">
</head>
<body>
    <div class="edit-container">
        <header class="edit-header">
            <h1>デジタル名刺編集</h1>
            <a href="dashboard.php" class="btn-back">ダッシュボードに戻る</a>
        </header>

        <div class="edit-content">
            <div class="edit-sidebar">
                <nav class="edit-nav">
                    <a href="#basic" class="nav-item active">基本情報</a>
                    <a href="#greetings" class="nav-item">挨拶文</a>
                    <a href="#tech-tools" class="nav-item">テックツール</a>
                    <a href="#communication" class="nav-item">コミュニケーション</a>
                </nav>
            </div>

            <div class="edit-main">
                <div id="basic-section" class="edit-section active">
                    <h2>基本情報</h2>
                    <form id="basic-form" class="edit-form">
                        <div class="form-group">
                            <label>会社名 <span class="required">*</span></label>
                            <input type="text" name="company_name" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label>ロゴ</label>
                            <div class="upload-area">
                                <input type="file" id="company_logo" accept="image/*" style="display: none;">
                                <button type="button" class="btn-upload" onclick="document.getElementById('company_logo').click()">アップロード</button>
                                <div class="upload-preview"></div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>顔写真</label>
                            <div class="upload-area">
                                <input type="file" id="profile_photo" accept="image/*" style="display: none;">
                                <button type="button" class="btn-upload" onclick="document.getElementById('profile_photo').click()">アップロード</button>
                                <div class="upload-preview"></div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>氏名 <span class="required">*</span></label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>ローマ字氏名</label>
                                <input type="text" name="name_romaji" class="form-control">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>部署</label>
                                <input type="text" name="branch_department" class="form-control">
                            </div>
                            <div class="form-group">
                                <label>役職</label>
                                <input type="text" name="position" class="form-control">
                            </div>
                        </div>

                        <button type="submit" class="btn-primary">保存</button>
                    </form>
                </div>

                <div id="greetings-section" class="edit-section">
                    <h2>挨拶文</h2>
                    <div id="greetings-list"></div>
                    <button type="button" class="btn-add" onclick="addGreeting()">挨拶文を追加</button>
                    <button type="button" class="btn-primary" onclick="saveGreetings()">保存</button>
                </div>

                <div id="tech-tools-section" class="edit-section">
                    <h2>テックツール</h2>
                    <p>表示するテックツールを選択してください（最低2つ以上）</p>
                    <div id="tech-tools-list"></div>
                    <button type="button" class="btn-primary" onclick="saveTechTools()">保存</button>
                </div>

                <div id="communication-section" class="edit-section">
                    <h2>コミュニケーション方法</h2>
                    <div id="communication-list"></div>
                    <button type="button" class="btn-add" onclick="addCommunicationMethod()">追加</button>
                    <button type="button" class="btn-primary" onclick="saveCommunicationMethods()">保存</button>
                </div>
            </div>

            <div class="edit-preview">
                <h3>プレビュー</h3>
                <div id="preview-content">
                    <p>プレビューを読み込み中...</p>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/edit.js"></script>
</body>
</html>

