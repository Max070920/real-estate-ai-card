<?php
/**
 * User Login API
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

    // バリデーション
    if (empty($input['email']) || empty($input['password'])) {
        sendErrorResponse('メールアドレスとパスワードを入力してください', 400);
    }

    $database = new Database();
    $db = $database->getConnection();

    // ユーザー検索
    $stmt = $db->prepare("
        SELECT u.*, bc.id as business_card_id, bc.url_slug
        FROM users u
        LEFT JOIN business_cards bc ON u.id = bc.user_id
        WHERE u.email = ?
    ");
    
    $stmt->execute([$input['email']]);
    $user = $stmt->fetch();

    if (!$user) {
        sendErrorResponse('メールアドレスまたはパスワードが正しくありません', 401);
    }

    // パスワード検証
    $passwordValid = verifyPassword($input['password'], $user['password_hash']);
    
    // プレーンテキストのパスワードが検出された場合、自動的に再ハッシュ化
    if (!$passwordValid && $input['password'] === $user['password_hash']) {
        // プレーンテキストが検出されたので、適切にハッシュ化して保存
        $newHash = hashPassword($input['password']);
        $updateStmt = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $updateStmt->execute([$newHash, $user['id']]);
        
        error_log("SECURITY: Plain text password detected and rehashed for user ID: " . $user['id']);
        $passwordValid = true; // 再ハッシュ化後、認証を許可
    }
    
    if (!$passwordValid) {
        sendErrorResponse('メールアドレスまたはパスワードが正しくありません', 401);
    }

    // ステータスチェック
    if ($user['status'] === 'suspended' || $user['status'] === 'cancelled') {
        sendErrorResponse('このアカウントは利用できません', 403);
    }

    // 最終ログイン時刻更新
    $stmt = $db->prepare("UPDATE users SET last_login_at = NOW() WHERE id = ?");
    $stmt->execute([$user['id']]);

    // セッション設定
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_type'] = $user['user_type'];

    sendSuccessResponse([
        'user_id' => $user['id'],
        'email' => $user['email'],
        'user_type' => $user['user_type'],
        'business_card_id' => $user['business_card_id'],
        'url_slug' => $user['url_slug'],
        'status' => $user['status']
    ], 'ログインに成功しました');

} catch (Exception $e) {
    error_log("Login Error: " . $e->getMessage());
    sendErrorResponse('サーバーエラーが発生しました', 500);
}

