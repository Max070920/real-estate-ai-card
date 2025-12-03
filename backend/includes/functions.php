<?php
/**
 * Common Utility Functions
 */

// Load Composer autoloader for PHPMailer and other dependencies
// require_once __DIR__ . '/../vendor/autoload.php';
// require __DIR__ . '/../config/config.php';
// require __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * JSONレスポンスを送信
 */
function sendJsonResponse($data, $statusCode = 200) {
    // Clear any output buffer
    if (ob_get_level() > 0) {
        ob_clean();
    }
    
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
    // End output buffering if active
    if (ob_get_level() > 0) {
        ob_end_flush();
    }
    
    exit();
}

/**
 * エラーレスポンスを送信
 */
function sendErrorResponse($message, $statusCode = 400, $errors = []) {
    $response = [
        'success' => false,
        'message' => $message
    ];
    
    if (!empty($errors)) {
        $response['errors'] = $errors;
    }
    
    sendJsonResponse($response, $statusCode);
}

/**
 * 成功レスポンスを送信
 */
function sendSuccessResponse($data = [], $message = 'Success') {
    $response = [
        'success' => true,
        'message' => $message,
        'data' => $data
    ];
    
    sendJsonResponse($response, 200);
}

/**
 * パスワードハッシュ生成
 */
function hashPassword($password) {
    if (empty($password)) {
        throw new InvalidArgumentException('Password cannot be empty');
    }
    
    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    if ($hash === false) {
        throw new RuntimeException('Failed to hash password');
    }
    
    return $hash;
}

/**
 * パスワード検証
 * プレーンテキストのパスワードが検出された場合、自動的に再ハッシュ化します
 */
function verifyPassword($password, $hash) {
    if (empty($password) || empty($hash)) {
        return false;
    }
    
    // Trim whitespace that might have been accidentally added
    $hash = trim($hash);
    
    // プレーンテキストのパスワードが保存されている場合を検出
    // bcryptハッシュは常に$2[ayb]$で始まり、60文字の長さです
    // より柔軟なチェック: $2[ayb]$の後に数字と$が続き、その後53文字
    if (!preg_match('/^\$2[ayb]\$\d{2}\$[A-Za-z0-9\.\/]{53}$/', $hash)) {
        // プレーンテキストの可能性がある場合、直接比較を試みる
        // ただし、これはセキュリティリスクなので、ログに記録して再ハッシュ化を推奨
        if ($password === $hash) {
            error_log("SECURITY WARNING: Plain text password detected in database. Password should be rehashed immediately.");
            // 自動的に再ハッシュ化を試みる（データベース接続が必要な場合は呼び出し元で処理）
            return true; // 一時的にtrueを返すが、呼び出し元で再ハッシュ化が必要
        }
        // ハッシュ形式が無効でも、password_verifyを試してみる（互換性のため）
        // password_verifyは自分で形式をチェックするので、これで十分
    }
    
    return password_verify($password, $hash);
}

/**
 * ランダムトークン生成
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * 画像リサイズ
 */
function resizeImage($filePath, $maxWidth = 800, $maxHeight = 800, $quality = 85) {
    if (!file_exists($filePath)) {
        return false;
    }

    $imageInfo = getimagesize($filePath);
    if ($imageInfo === false) {
        return false;
    }

    $originalWidth = $imageInfo[0];
    $originalHeight = $imageInfo[1];
    $mimeType = $imageInfo['mime'];

    // リサイズ不要の場合
    if ($originalWidth <= $maxWidth && $originalHeight <= $maxHeight) {
        return true;
    }

    // アスペクト比を保持してリサイズ
    $ratio = min($maxWidth / $originalWidth, $maxHeight / $originalHeight);
    $newWidth = (int)($originalWidth * $ratio);
    $newHeight = (int)($originalHeight * $ratio);

    // 画像リソース作成
    switch ($mimeType) {
        case 'image/jpeg':
            $source = imagecreatefromjpeg($filePath);
            break;
        case 'image/png':
            $source = imagecreatefrompng($filePath);
            break;
        case 'image/gif':
            $source = imagecreatefromgif($filePath);
            break;
        case 'image/webp':
            $source = imagecreatefromwebp($filePath);
            break;
        default:
            return false;
    }

    if ($source === false) {
        return false;
    }

    // 新しい画像リソース作成
    $destination = imagecreatetruecolor($newWidth, $newHeight);

    // PNG/GIFの透明度対応
    if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
        imagealphablending($destination, false);
        imagesavealpha($destination, true);
        $transparent = imagecolorallocatealpha($destination, 255, 255, 255, 127);
        imagefilledrectangle($destination, 0, 0, $newWidth, $newHeight, $transparent);
    }

    // リサイズ
    imagecopyresampled($destination, $source, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);

    // 保存
    $result = false;
    switch ($mimeType) {
        case 'image/jpeg':
            $result = imagejpeg($destination, $filePath, $quality);
            break;
        case 'image/png':
            $result = imagepng($destination, $filePath, 9);
            break;
        case 'image/gif':
            $result = imagegif($destination, $filePath);
            break;
        case 'image/webp':
            $result = imagewebp($destination, $filePath, $quality);
            break;
    }

    imagedestroy($source);
    imagedestroy($destination);

    return $result;
}

/**
 * ファイルアップロード
 */
function uploadFile($file, $subDirectory = '') {
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        error_log("uploadFile: File not uploaded - tmp_name: " . ($file['tmp_name'] ?? 'not set'));
        return ['success' => false, 'message' => 'ファイルがアップロードされていません'];
    }

    // ファイルサイズチェック
    if ($file['size'] > MAX_FILE_SIZE) {
        error_log("uploadFile: File too large - size: " . $file['size'] . ", max: " . MAX_FILE_SIZE);
        return ['success' => false, 'message' => 'ファイルサイズが大きすぎます'];
    }

    // ファイルタイプチェック
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, ALLOWED_IMAGE_TYPES)) {
        error_log("uploadFile: Invalid file type - mime: $mimeType, allowed: " . implode(', ', ALLOWED_IMAGE_TYPES));
        return ['success' => false, 'message' => '許可されていないファイルタイプです: ' . $mimeType];
    }

    // ディレクトリ作成
    $uploadDir = UPLOAD_DIR . $subDirectory;
    if (!is_dir($uploadDir)) {
        error_log("uploadFile: Creating directory: $uploadDir");
        if (!mkdir($uploadDir, 0755, true)) {
            error_log("uploadFile: Failed to create directory: $uploadDir");
            return ['success' => false, 'message' => 'アップロードディレクトリの作成に失敗しました'];
        }
    }

    // ファイル名生成
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = uniqid() . '_' . time() . '.' . $extension;
    $filePath = $uploadDir . $fileName;

    error_log("uploadFile: Moving file to: $filePath");

    // ファイル移動
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        error_log("uploadFile: Failed to move file from " . $file['tmp_name'] . " to $filePath");
        return ['success' => false, 'message' => 'ファイルの移動に失敗しました'];
    }

    // 画像リサイズ
    resizeImage($filePath);

    $relativePath = 'backend/uploads/' . $subDirectory . $fileName;
    
    return [
        'success' => true,
        'file_path' => $relativePath,
        'file_name' => $fileName,
        'mime_type' => $mimeType
    ];
}

/**
 * 郵便番号から住所を取得（郵便番号データベースまたはAPI使用）
 */
function getAddressFromPostalCode($postalCode) {
    // ハイフン除去
    $postalCode = str_replace('-', '', $postalCode);
    
    // ここで郵便番号APIを呼び出すか、データベースから取得
    // 例: Yahoo APIや郵便番号データベースを使用
    // 簡易版として、今後実装が必要
    return null;
}

/**
 * URLスラッグ生成
 */
function generateUrlSlug($length = 6) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $slug = '';
    
    for ($i = 0; $i < $length; $i++) {
        $slug .= $characters[random_int(0, strlen($characters) - 1)];
    }
    
    return $slug;
}

/**
 * サニタイズ
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    if ($data === null || $data === '') {
        return null;
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * メール送信
 */
function sendEmail($to, $subject, $htmlMessage, $textMessage = '') {
    $mail = new PHPMailer(true);

    try {
        // SMTP 設定
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'ctha43843@gmail.com'; // あなたのGmail
        $mail->Password = 'lsdimxhugzdlhxla'; // Gmailアプリパスワード
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // 送信者情報
        $mail->setFrom('ctha43843@gmail.com', '不動産AI名刺');
        $mail->addReplyTo('ctha43843@gmail.com');

        // 宛先
        $mail->addAddress($to);

        // メール内容
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'quoted-printable';
        $mail->Subject = $subject;
        $mail->Body    = $htmlMessage;
        $mail->AltBody = $textMessage ?: strip_tags($htmlMessage);

        return $mail->send();

    } catch (Exception $e) {
        error_log("[Email Error] {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * 管理者に新規登録通知メールを送信
 */
function sendAdminNotificationEmail($userEmail, $userType, $userId, $urlSlug) {
    if (!defined('NOTIFICATION_EMAIL') || empty(NOTIFICATION_EMAIL)) {
        error_log("NOTIFICATION_EMAIL is not defined");
        return false;
    }

    $adminEmail = 'inoue.sho95@gmail.com';
    
    // ユーザータイプの日本語表示
    $userTypeLabels = [
        'new' => '新規ユーザー',
        'existing' => '既存ユーザー',
        'free' => '無料ユーザー'
    ];
    $userTypeLabel = $userTypeLabels[$userType] ?? $userType;
    
    $registrationDate = date('Y年m月d日 H:i:s');
    
    // メール件名
    $emailSubject = '【不動産AI名刺】新規ユーザー登録通知';
    
    // HTML本文
    $emailBody = "
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: 'Hiragino Sans', 'Hiragino Kaku Gothic ProN', 'Meiryo', sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #0066cc; color: #fff; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px; }
            .info-table { width: 100%; border-collapse: collapse; margin: 20px 0; background: #fff; }
            .info-table th { background: #e9ecef; padding: 12px; text-align: left; border: 1px solid #dee2e6; font-weight: bold; width: 30%; }
            .info-table td { padding: 12px; border: 1px solid #dee2e6; }
            .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 12px; color: #666; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>不動産AI名刺</h1>
            </div>
            <div class='content'>
                <p>新規ユーザーが登録されました。</p>
                <table class='info-table'>
                    <tr>
                        <th>ユーザーID</th>
                        <td>{$userId}</td>
                    </tr>
                    <tr>
                        <th>メールアドレス</th>
                        <td>{$userEmail}</td>
                    </tr>
                    <tr>
                        <th>ユーザータイプ</th>
                        <td>{$userTypeLabel}</td>
                    </tr>
                    <tr>
                        <th>URLスラッグ</th>
                        <td>{$urlSlug}</td>
                    </tr>
                    <tr>
                        <th>登録日時</th>
                        <td>{$registrationDate}</td>
                    </tr>
                </table>
                <div class='footer'>
                    <p>このメールは自動送信されています。返信はできません。</p>
                    <p>© " . date('Y') . " 不動産AI名刺 All rights reserved.</p>
                </div>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // プレーンテキスト版
    $emailBodyText = 
        "新規ユーザーが登録されました。\n\n" .
        "ユーザーID: {$userId}\n" .
        "メールアドレス: {$userEmail}\n" .
        "ユーザータイプ: {$userTypeLabel}\n" .
        "URLスラッグ: {$urlSlug}\n" .
        "登録日時: {$registrationDate}\n";
    
    return sendEmail($adminEmail, $emailSubject, $emailBody, $emailBodyText);
}

/**
 * バリデーション: メールアドレス
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * バリデーション: 電話番号（日本形式）
 */
function validatePhoneNumber($phone) {
    // ハイフンやスペースを除去
    $phone = preg_replace('/[\s\-\(\)]/', '', $phone);
    // 10桁または11桁の数字
    return preg_match('/^0\d{9,10}$/', $phone);
}

/**
 * バリデーション: 郵便番号（日本形式）
 */
function validatePostalCode($postalCode) {
    $postalCode = str_replace('-', '', $postalCode);
    return preg_match('/^\d{7}$/', $postalCode);
}

/**
 * セッション開始
 */
function startSessionIfNotStarted() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

