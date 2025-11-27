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

    if (!$admin) {
        sendErrorResponse('メールアドレスまたはパスワードが正しくありません', 401);
    }

    // パスワード検証
    //$storedHash = trim($admin['password_hash']); // Trim any whitespace
    // $passwordValid = verifyPassword($input['password'], $storedHash);
    if($admin['password_hash'] === $input['password']) {
        $passwordValid = true;
    }
    // デバッグ用ログ（本番環境では削除）
    // if (ENVIRONMENT === 'development') {
    //     error_log("Admin Login Debug - Email: " . $input['email']);
    //     error_log("Admin Login Debug - Hash length: " . strlen($storedHash));
    //     error_log("Admin Login Debug - Hash starts with: " . substr($storedHash, 0, 7));
    //     error_log("Admin Login Debug - Password valid: " . ($passwordValid ? 'true' : 'false'));
    // }
    
    // プレーンテキストのパスワードが検出された場合、自動的に再ハッシュ化
    // if (!$passwordValid && $input['password'] === $storedHash) {
    //     // プレーンテキストが検出されたので、適切にハッシュ化して保存
    //     $newHash = hashPassword($input['password']);
    //     $updateStmt = $db->prepare("UPDATE admins SET password_hash = ?, last_password_change = NOW() WHERE id = ?");
    //     $updateStmt->execute([$newHash, $admin['id']]);
        
    //     error_log("SECURITY: Plain text password detected and rehashed for admin ID: " . $admin['id']);
    //     $passwordValid = true; // 再ハッシュ化後、認証を許可
    // }
    
    // ハッシュが正しい形式だが検証に失敗した場合、直接password_verifyを試す
    // if (!$passwordValid && preg_match('/^\$2[ayb]\$\d{2}\$/', $storedHash)) {
    //     $directVerify = password_verify($input['password'], $storedHash);
    //     if ($directVerify) {
    //         error_log("Admin Login: verifyPassword failed but password_verify succeeded - possible function issue");
    //         $passwordValid = true;
    //     }
    // }
    
    if (!$passwordValid) {
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

