<?php
/**
 * New User Registration Page (Step 1: Account Registration)
 */
require_once __DIR__ . '/../backend/config/config.php';
require_once __DIR__ . '/../backend/includes/functions.php';

startSessionIfNotStarted();

$userType = $_GET['type'] ?? 'new'; // new, existing, free
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>アカウント作成 - 不動産AI名刺</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/register.css">
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <a href="index.php" class="logo-link">
                <img src="assets/images/logo.png" alt="不動産AI名刺">
            </a>
        </div>

        <div class="register-content">

            <!-- Step 1: Account Registration -->
            <div id="step-1" class="register-step active">
                <h1>アカウント作成</h1>
                <!-- <p class="step-description">必要な情報を入力して、あなた専用のデジタル名刺を作成しましょう</p> -->

                <form id="register-form" class="register-form">
                    <input type="hidden" name="user_type" value="<?php echo htmlspecialchars($userType); ?>">

                    <?php if ($userType === 'existing'): ?>
                    <div class="form-group">
                        <label>既存URL（既存利用者のみ）</label>
                        <input type="text" name="existing_url" class="form-control" placeholder="既存のサービスURLを入力">
                    </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label>メールアドレス <span class="required">*</span></label>
                        <input type="email" name="email" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>パスワード <span class="required">*</span></label>
                        <input type="password" name="password" id="password" class="form-control" minlength="8" required>
                        <small>8文字以上で入力してください</small>
                    </div>

                    <div class="form-group">
                        <label>パスワード（確認） <span class="required">*</span></label>
                        <input type="password" name="password_confirm" id="password_confirm" class="form-control" minlength="8" required>
                        <small id="password-error" style="color: #e74c3c; display: none;">パスワードが一致しません</small>
                    </div>

                    <div class="form-group">
                        <label>携帯電話番号 <span class="required">*</span></label>
                        <input type="tel" name="phone_number" class="form-control" required>
                    </div>

                    <div class="form-group checkbox-group">
                        <label>
                            <input type="checkbox" name="agree_terms" required>
                            <a href="terms.php" target="_blank">利用規約</a>に同意する
                        </label>
                    </div>

                    <div class="form-group checkbox-group">
                        <label>
                            <input type="checkbox" name="agree_privacy" required>
                            <a href="privacy.php" target="_blank">プライバシーポリシー</a>に同意する
                        </label>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-primary btn-large">次へ</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Password confirmation validation
        const passwordField = document.getElementById('password');
        const passwordConfirmField = document.getElementById('password_confirm');
        const passwordError = document.getElementById('password-error');
        
        function validatePasswordMatch() {
            const password = passwordField.value;
            const passwordConfirm = passwordConfirmField.value;
            
            if (passwordConfirm && password !== passwordConfirm) {
                passwordError.style.display = 'block';
                passwordConfirmField.setCustomValidity('パスワードが一致しません');
                return false;
            } else {
                passwordError.style.display = 'none';
                passwordConfirmField.setCustomValidity('');
                return true;
            }
        }
        
        passwordField.addEventListener('input', validatePasswordMatch);
        passwordConfirmField.addEventListener('input', validatePasswordMatch);
        
        // Step 1: Account Registration
        document.getElementById('register-form')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            // Validate password match
            if (!validatePasswordMatch()) {
                passwordConfirmField.focus();
                return;
            }
            
            const formDataObj = new FormData(e.target);
            const data = Object.fromEntries(formDataObj);
            
            // Remove password_confirm from data before sending
            delete data.password_confirm;
            
            try {
                const response = await fetch('../backend/api/auth/register.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Redirect to register.php to continue with remaining steps
                    window.location.href = 'register.php';
                } else {
                    alert(result.message || '登録に失敗しました');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('エラーが発生しました');
            }
        });
    </script>
</body>
</html>

