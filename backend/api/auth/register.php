<?php
/**
 * User Registration API
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
    $errors = [];

    if (empty($input['email']) || !validateEmail($input['email'])) {
        $errors['email'] = '有効なメールアドレスを入力してください';
    }

    if (empty($input['password']) || strlen($input['password']) < 8) {
        $errors['password'] = 'パスワードは8文字以上で入力してください';
    }

    if (empty($input['phone_number']) || !validatePhoneNumber($input['phone_number'])) {
        $errors['phone_number'] = '有効な電話番号を入力してください';
    }

    if (empty($input['user_type']) || !in_array($input['user_type'], ['new', 'existing', 'free'])) {
        $errors['user_type'] = 'ユーザータイプを選択してください';
    }

    if (!empty($errors)) {
        sendErrorResponse('入力内容に誤りがあります', 400, $errors);
    }

    $database = new Database();
    $db = $database->getConnection();

    // メールアドレスの重複チェック
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$input['email']]);
    if ($stmt->fetch()) {
        sendErrorResponse('このメールアドレスは既に登録されています', 400);
    }

    // トークン生成
    $verificationToken = generateToken(32);
    
    // パスワードハッシュ化
    $passwordHash = hashPassword($input['password']);

    // 既存ユーザーの場合、既存URLをチェック
    $existingUrl = null;
    if ($input['user_type'] === 'existing' && !empty($input['existing_url'])) {
        $existingUrl = sanitizeInput($input['existing_url']);
    }

    // ユーザー登録
    $stmt = $db->prepare("
        INSERT INTO users (email, password_hash, phone_number, user_type, verification_token, status)
        VALUES (?, ?, ?, ?, ?, 'pending')
    ");
    
    $stmt->execute([
        $input['email'],
        $passwordHash,
        $input['phone_number'],
        $input['user_type'],
        $verificationToken
    ]);

    $userId = $db->lastInsertId();

    // メール認証の送信（簡易版）
    $verificationLink = BASE_URL . "/frontend/auth/verify.php?token=" . $verificationToken;
    
    // 実際のメール送信処理はここで実装
    // sendEmail($input['email'], 'メール認証', $verificationLink);

    // 既存ユーザーの場合、ビジネスカードのURLスラッグを設定
    $urlSlug = null;
    if ($input['user_type'] === 'existing' && $existingUrl) {
        // 既存URLからスラッグを抽出
        $urlParts = explode('/', trim($existingUrl, '/'));
        $urlSlug = end($urlParts);
    } elseif ($input['user_type'] === 'new') {
        // 新規ユーザー: 6桁の連続数字を発番
        $stmt = $db->prepare("SELECT current_number FROM tech_tool_url_counter LIMIT 1");
        $stmt->execute();
        $counter = $stmt->fetch();
        $urlSlug = str_pad($counter['current_number'], 6, '0', STR_PAD_LEFT);
        
        // カウンターをインクリメント
        $stmt = $db->prepare("UPDATE tech_tool_url_counter SET current_number = current_number + 1");
        $stmt->execute();
    } else {
        // 無料版も同様に連番を発番
        $stmt = $db->prepare("SELECT current_number FROM tech_tool_url_counter LIMIT 1");
        $stmt->execute();
        $counter = $stmt->fetch();
        $urlSlug = str_pad($counter['current_number'], 6, '0', STR_PAD_LEFT);
        
        $stmt = $db->prepare("UPDATE tech_tool_url_counter SET current_number = current_number + 1");
        $stmt->execute();
    }

    // ビジネスカードの初期レコード作成
    $stmt = $db->prepare("
        INSERT INTO business_cards (user_id, url_slug, name, mobile_phone)
        VALUES (?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $userId,
        $urlSlug,
        !empty($input['name']) ? $input['name'] : '',
        $input['phone_number']
    ]);

    // セッションを設定してユーザーをログイン状態にする
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_email'] = $input['email'];
    $_SESSION['user_type'] = $input['user_type'];

    sendSuccessResponse([
        'user_id' => $userId,
        'email' => $input['email'],
        'user_type' => $input['user_type'],
        'url_slug' => $urlSlug,
        'message' => '登録が完了しました。メール認証を行ってください。'
    ], 'ユーザー登録が完了しました');

} catch (Exception $e) {
    error_log("Registration Error: " . $e->getMessage());
    sendErrorResponse('サーバーエラーが発生しました', 500);
}

