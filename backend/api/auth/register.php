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

    // メール認証リンク (URLエンコード)
    $verificationLink = BASE_URL . "/frontend/auth/verify.php?token=" . urlencode($verificationToken);

    // 件名
    $emailSubject = "【不動産AI名刺】メール認証のお願い";

    // HTMLメール本文（インラインCSS優先）
    $emailBodyHtml = '
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="color-scheme" content="light dark">
<meta name="supported-color-schemes" content="light dark">
<title>メール認証</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f4;font-family:\'Hiragino Sans\',\'Meiryo\',sans-serif;line-height:1.7;">
<table width="100%" cellpadding="0" cellspacing="0">
<tr><td align="center" style="padding:20px;">
<table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:10px;overflow:hidden;">
<tr>
<td align="center" style="background:#0066cc;padding:20px;">
<h1 style="margin:0;color:#ffffff;font-size:22px;">不動産AI名刺</h1>
</td>
</tr>
<tr>
<td style="padding:30px;color:#333;font-size:15px;">
<p>この度は、不動産AI名刺にご登録いただき、誠にありがとうございます。</p>
<p>メール認証を完了するため、下記ボタンをクリックしてください。</p>
<p style="text-align:center;margin:25px 0;">
<a href="' . $verificationLink . '" 
style="display:inline-block;padding:14px 28px;background:#0066cc;color:#ffffff;text-decoration:none;border-radius:6px;font-size:16px;">
メール認証を完了する
</a>
</p>
<p>もしクリックできない場合は、以下のURLをコピーしてブラウザに貼り付けてください：</p>
<p style="word-break:break-all;background:#fafafa;padding:12px;border-radius:6px;font-size:12px;border:1px solid #e5e5e5;">' . $verificationLink . '</p>
<p><strong>※このリンクは24時間有効です。</strong></p>
<p>このメールに覚えがない場合は破棄してください。</p>
<hr style="border:none;border-top:1px solid #e5e5e5;margin:30px 0;">
<p style="font-size:11px;color:#777;">このメールは自動送信されています。返信はできません。</p>
<p style="font-size:11px;color:#777;">© ' . date("Y") . ' 不動産AI名刺 All rights reserved.</p>
</td>
</tr>
</table>
</td></tr>
</table>
</body>
</html>
';

    // プレーンテキスト版（スパム対策）
    $emailBodyText = "不動産AI名刺へご登録ありがとうございます。\n\n" .
        "以下のリンクをクリックしてメール認証を完了してください（24時間有効）：\n" .
        $verificationLink . "\n\n" .
        "心当たりがない場合は破棄してください。\n";

    // メール送信（プレーンテキスト＋HTML）
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

