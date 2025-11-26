<?php
/**
 * Admin Login Page
 */
require_once __DIR__ . '/../../backend/config/config.php';
require_once __DIR__ . '/../../backend/includes/functions.php';

startSessionIfNotStarted();

// 既にログイン済みの場合はダッシュボードへ
if (!empty($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理者ログイン - 不動産AI名刺</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="admin-login-container">
        <div class="admin-login-box">
            <h1>管理者ログイン</h1>
            <form id="admin-login-form">
                <div class="form-group">
                    <label>メールアドレス</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>パスワード</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn-primary btn-block">ログイン</button>
            </form>
            <div id="error-message" class="error-message" style="display: none;"></div>
        </div>
    </div>
    
    <script>
        document.getElementById('admin-login-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData);
            
            try {
                const response = await fetch('../../backend/api/admin/login.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    window.location.href = 'dashboard.php';
                } else {
                    document.getElementById('error-message').textContent = result.message || 'ログインに失敗しました';
                    document.getElementById('error-message').style.display = 'block';
                }
            } catch (error) {
                console.error('Error:', error);
                document.getElementById('error-message').textContent = 'エラーが発生しました';
                document.getElementById('error-message').style.display = 'block';
            }
        });
    </script>
</body>
</html>

