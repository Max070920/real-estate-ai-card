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
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/edit.css">
</head>
<body>
    <?php 
    $showNavLinks = false; // Hide nav links on edit page
    include __DIR__ . '/includes/header.php'; 
    ?>
    
    <div class="edit-container">
        <header class="edit-header">
            <h1>デジタル名刺編集</h1>
            <a href="index.php" class="btn-back">ホームに戻る</a>
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
                            <div class="upload-area" data-upload-id="company_logo">
                                <input type="file" id="company_logo" accept="image/*" style="display: none;">
                                <button type="button" class="btn-upload" onclick="document.getElementById('company_logo').click()">アップロード</button>
                                <div class="upload-preview"></div>
                                <small>ファイルを選択するか、ここにドラッグ&ドロップしてください</small>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>顔写真</label>
                            <div class="upload-area" data-upload-id="profile_photo">
                                <input type="file" id="profile_photo" accept="image/*" style="display: none;">
                                <button type="button" class="btn-upload" onclick="document.getElementById('profile_photo').click()">アップロード</button>
                                <div class="upload-preview"></div>
                                <small>ファイルを選択するか、ここにドラッグ&ドロップしてください</small>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>姓 <span class="required">*</span></label>
                                <input type="text" name="last_name" id="edit_last_name" class="form-control" required placeholder="例：山田">
                            </div>
                            <div class="form-group">
                                <label>名 <span class="required">*</span></label>
                                <input type="text" name="first_name" id="edit_first_name" class="form-control" required placeholder="例：太郎">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>ローマ字姓</label>
                                <input type="text" name="last_name_romaji" id="edit_last_name_romaji" class="form-control" placeholder="例：Yamada">
                            </div>
                            <div class="form-group">
                                <label>ローマ字名</label>
                                <input type="text" name="first_name_romaji" id="edit_first_name_romaji" class="form-control" placeholder="例：Taro">
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
    <script>
        // 姓と名を結合して送信する処理
        document.addEventListener('DOMContentLoaded', function() {
            const basicForm = document.getElementById('basic-form');
            if (basicForm) {
                basicForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(basicForm);
                    const data = {};
                    
                    // すべてのフィールドを取得
                    for (let [key, value] of formData.entries()) {
                        data[key] = value;
                    }
                    
                    // 姓と名を結合
                    const lastName = data.last_name || '';
                    const firstName = data.first_name || '';
                    data.name = (lastName + ' ' + firstName).trim();
                    
                    // ローマ字姓と名を結合
                    const lastNameRomaji = data.last_name_romaji || '';
                    const firstNameRomaji = data.first_name_romaji || '';
                    data.name_romaji = (lastNameRomaji + ' ' + firstNameRomaji).trim();
                    
                    // 不要なフィールドを削除
                    delete data.last_name;
                    delete data.first_name;
                    delete data.last_name_romaji;
                    delete data.first_name_romaji;
                    
                    // APIに送信
                    fetch('../backend/api/business-card/update.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(data),
                        credentials: 'include'
                    })
                    .then(response => response.json())
                    .then(result => {
                        if (result.success) {
                            alert('保存しました');
                            // 必要に応じてページをリロード
                            location.reload();
                        } else {
                            alert('保存に失敗しました: ' + result.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('エラーが発生しました');
                    });
                });
            }
            
            // データ読み込み時にnameを分割
            // 既存のedit.jsがデータを読み込む場合は、その処理を上書き
            if (typeof loadBusinessCardData === 'function') {
                const originalLoad = loadBusinessCardData;
                loadBusinessCardData = function(data) {
                    originalLoad(data);
                    
                    // nameを姓と名に分割
                    if (data.name) {
                        const nameParts = data.name.trim().split(/\s+/);
                        if (nameParts.length >= 2) {
                            document.getElementById('edit_last_name').value = nameParts[0];
                            document.getElementById('edit_first_name').value = nameParts.slice(1).join(' ');
                        } else {
                            document.getElementById('edit_last_name').value = data.name;
                        }
                    }
                    
                    // name_romajiを姓と名に分割
                    if (data.name_romaji) {
                        const romajiParts = data.name_romaji.trim().split(/\s+/);
                        if (romajiParts.length >= 2) {
                            document.getElementById('edit_last_name_romaji').value = romajiParts[0];
                            document.getElementById('edit_first_name_romaji').value = romajiParts.slice(1).join(' ');
                        } else {
                            document.getElementById('edit_last_name_romaji').value = data.name_romaji;
                        }
                    }
                };
            }
        });
        
        // 漢字からローマ字への自動変換機能（edit.php用）
        document.addEventListener('DOMContentLoaded', function() {
            const lastNameInput = document.getElementById('edit_last_name');
            const firstNameInput = document.getElementById('edit_first_name');
            const lastNameRomajiInput = document.getElementById('edit_last_name_romaji');
            const firstNameRomajiInput = document.getElementById('edit_first_name_romaji');
            
            // 簡易的な変換テーブル
            const nameConversionMap = {
                '山田': 'Yamada', '田中': 'Tanaka', '佐藤': 'Sato', '鈴木': 'Suzuki',
                '高橋': 'Takahashi', '伊藤': 'Ito', '渡辺': 'Watanabe', '中村': 'Nakamura',
                '小林': 'Kobayashi', '加藤': 'Kato', '吉田': 'Yoshida', '山本': 'Yamamoto',
                '松本': 'Matsumoto', '井上': 'Inoue', '木村': 'Kimura', '林': 'Hayashi',
                '斎藤': 'Saito', '清水': 'Shimizu', '山崎': 'Yamazaki', '中島': 'Nakajima',
                '前田': 'Maeda', '藤田': 'Fujita', '後藤': 'Goto', '近藤': 'Kondo',
                '太郎': 'Taro', '次郎': 'Jiro', '三郎': 'Saburo', '花子': 'Hanako',
                '一郎': 'Ichiro', '二郎': 'Jiro', '三郎': 'Saburo', '美咲': 'Misaki',
                'さくら': 'Sakura', 'あかり': 'Akari', 'ひなた': 'Hinata', 'みお': 'Mio'
            };
            
            function convertToRomaji(japanese) {
                if (!japanese) return '';
                if (nameConversionMap[japanese]) {
                    return nameConversionMap[japanese];
                }
                return '';
            }
            
            if (lastNameInput && lastNameRomajiInput) {
                let lastNameTimeout;
                lastNameInput.addEventListener('input', function() {
                    clearTimeout(lastNameTimeout);
                    const value = this.value.trim();
                    if (!lastNameRomajiInput.value.trim() && value) {
                        lastNameTimeout = setTimeout(function() {
                            const romaji = convertToRomaji(value);
                            if (romaji) {
                                lastNameRomajiInput.value = romaji;
                            }
                        }, 500);
                    }
                });
            }
            
            if (firstNameInput && firstNameRomajiInput) {
                let firstNameTimeout;
                firstNameInput.addEventListener('input', function() {
                    clearTimeout(firstNameTimeout);
                    const value = this.value.trim();
                    if (!firstNameRomajiInput.value.trim() && value) {
                        firstNameTimeout = setTimeout(function() {
                            const romaji = convertToRomaji(value);
                            if (romaji) {
                                firstNameRomajiInput.value = romaji;
                            }
                        }, 500);
                    }
                });
            }
        });
        
        // ドラッグ&ドロップ機能の初期化（edit.php用）
        document.addEventListener('DOMContentLoaded', function() {
            // すべてのアップロードエリアにドラッグ&ドロップ機能を追加
            document.querySelectorAll('.upload-area').forEach(uploadArea => {
                const fileInput = uploadArea.querySelector('input[type="file"]');
                if (!fileInput) return;
                
                // ドラッグオーバー時の処理
                uploadArea.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    uploadArea.classList.add('drag-over');
                });
                
                // ドラッグリーブ時の処理
                uploadArea.addEventListener('dragleave', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    uploadArea.classList.remove('drag-over');
                });
                
                // ドロップ時の処理
                uploadArea.addEventListener('drop', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    uploadArea.classList.remove('drag-over');
                    
                    const files = e.dataTransfer.files;
                    if (files.length > 0) {
                        const file = files[0];
                        // 画像ファイルかチェック
                        if (file.type.startsWith('image/')) {
                            fileInput.files = files;
                            // ファイル選択イベントをトリガー
                            const event = new Event('change', { bubbles: true });
                            fileInput.dispatchEvent(event);
                        } else {
                            alert('画像ファイルを選択してください');
                        }
                    }
                });
                
                // クリックでファイル選択も可能
                uploadArea.addEventListener('click', function(e) {
                    // ボタンやプレビュー画像をクリックした場合は除外
                    if (e.target.tagName !== 'BUTTON' && e.target.tagName !== 'IMG') {
                        fileInput.click();
                    }
                });
            });
        });
    </script>
</body>
</html>

