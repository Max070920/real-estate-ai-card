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

    // パスワードハッシュ
    $passwordHash = hashPassword($input['password']);

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

    // 🔹 URLは必ずドメイン + HTTPS（IP NG）
    $verificationLink = "http://103.179.45.108/php/frontend/auth/verify.php?token=" . urlencode($verificationToken);

    // 件名（短くシンプル → スパム回避）
    $emailSubject = "メール認証のお願い";

    // HTML本文（シンプル → スパム回避）
    $emailBodyHtml = "
<p>不動産AI名刺へのご登録ありがとうございます。</p>
<p>下記リンクをクリックしてメール認証を完了してください：</p>
<p><a href='$verificationLink'>$verificationLink</a></p>
<p>※ このリンクは24時間有効です。</p>
<p>このメールに覚えがない場合は破棄してください。</p>
";

    // プレーンテキスト（必須）
    $emailBodyText =
        "不動産AI名刺へのご登録ありがとうございます。\n\n" .
        "以下のリンクをクリックしてメール認証を完了してください（24時間有効）：\n" .
        "$verificationLink\n\n" .
        "このメールに覚えがない場合は破棄してください。\n";

    // 送信
    $emailSent = sendEmail($input['email'], $emailSubject, $emailBodyHtml, $emailBodyText);

    if (!$emailSent) {
        error_log("[Email Error] Verification email send failed: " . $input['email']);
    }



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

