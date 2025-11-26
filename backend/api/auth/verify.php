<?php
/**
 * Email Verification API
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json; charset=UTF-8');

try {
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method !== 'GET' && $method !== 'POST') {
        sendErrorResponse('Method not allowed', 405);
    }

    $token = $_GET['token'] ?? ($_POST['token'] ?? '');

    if (empty($token)) {
        sendErrorResponse('認証トークンが必要です', 400);
    }

    $database = new Database();
    $db = $database->getConnection();

    // トークン検証
    $stmt = $db->prepare("SELECT id, email FROM users WHERE verification_token = ? AND email_verified = 0");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if (!$user) {
        sendErrorResponse('無効な認証トークンです', 400);
    }

    // メール認証完了
    $stmt = $db->prepare("UPDATE users SET email_verified = 1, verification_token = NULL, status = 'active' WHERE id = ?");
    $stmt->execute([$user['id']]);

    sendSuccessResponse([
        'user_id' => $user['id'],
        'email' => $user['email']
    ], 'メール認証が完了しました');

} catch (Exception $e) {
    error_log("Verification Error: " . $e->getMessage());
    sendErrorResponse('サーバーエラーが発生しました', 500);
}

