<?php
/**
 * Admin Login API
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

startSessionIfNotStarted();

header('Content-Type: application/json; charset=UTF-8');

try {
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method !== 'POST') {
        sendErrorResponse('Method not allowed', 405);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }

    if (empty($input['email']) || empty($input['password'])) {
        sendErrorResponse('メールアドレスとパスワードを入力してください', 400);
    }

    $database = new Database();
    $db = $database->getConnection();

    $stmt = $db->prepare("SELECT * FROM admins WHERE email = ?");
    $stmt->execute([$input['email']]);
    $admin = $stmt->fetch();

    if (!$admin || !verifyPassword($input['password'], $admin['password_hash'])) {
        sendErrorResponse('メールアドレスまたはパスワードが正しくありません', 401);
    }

    // 最終ログイン時刻更新
    $stmt = $db->prepare("UPDATE admins SET last_login_at = NOW() WHERE id = ?");
    $stmt->execute([$admin['id']]);

    // セッション設定
    $_SESSION['admin_id'] = $admin['id'];
    $_SESSION['admin_email'] = $admin['email'];
    $_SESSION['admin_role'] = $admin['role'];

    sendSuccessResponse([
        'admin_id' => $admin['id'],
        'email' => $admin['email'],
        'role' => $admin['role']
    ], 'ログインに成功しました');

} catch (Exception $e) {
    error_log("Admin Login Error: " . $e->getMessage());
    sendErrorResponse('サーバーエラーが発生しました', 500);
}

