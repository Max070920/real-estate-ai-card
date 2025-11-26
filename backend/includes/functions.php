<?php
/**
 * Common Utility Functions
 */

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
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * パスワード検証
 */
function verifyPassword($password, $hash) {
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
function sendEmail($to, $subject, $message, $headers = []) {
    $defaultHeaders = [
        'From: ' . NOTIFICATION_EMAIL,
        'Reply-To: ' . NOTIFICATION_EMAIL,
        'X-Mailer: PHP/' . phpversion(),
        'Content-Type: text/html; charset=UTF-8'
    ];
    
    $allHeaders = array_merge($defaultHeaders, $headers);
    
    return mail($to, $subject, $message, implode("\r\n", $allHeaders));
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

