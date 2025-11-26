<?php
/**
 * Create Payment Intent API (Stripe)
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../middleware/auth.php';

// Stripe SDK読み込み（Composer経由を想定）
// require_once __DIR__ . '/../../vendor/autoload.php';
// use Stripe\Stripe;
// use Stripe\PaymentIntent;
// use Stripe\Subscription;

header('Content-Type: application/json; charset=UTF-8');

try {
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method !== 'POST') {
        sendErrorResponse('Method not allowed', 405);
    }

    $userId = requireAuth();

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }

    $database = new Database();
    $db = $database->getConnection();

    // ユーザー情報取得
    $stmt = $db->prepare("
        SELECT u.user_type, bc.id as business_card_id
        FROM users u
        JOIN business_cards bc ON u.id = bc.user_id
        WHERE u.id = ?
    ");
    $stmt->execute([$userId]);
    $userInfo = $stmt->fetch();

    if (!$userInfo) {
        sendErrorResponse('ユーザー情報が見つかりません', 404);
    }

    // 価格計算
    $paymentType = $input['payment_type'] ?? $userInfo['user_type'];
    $paymentMethod = $input['payment_method'] ?? 'credit_card';

    $amount = 0;
    $taxAmount = 0;
    $totalAmount = 0;
    $monthlyAmount = 0;

    if ($paymentType === 'new') {
        $amount = PRICING_NEW_USER_INITIAL;
        $taxAmount = $amount * TAX_RATE;
        $totalAmount = $amount + $taxAmount;
        
        // 月額料金も計算
        $monthlyAmount = PRICING_NEW_USER_MONTHLY;
    } elseif ($paymentType === 'existing') {
        $amount = PRICING_EXISTING_USER_INITIAL;
        $taxAmount = $amount * TAX_RATE;
        $totalAmount = $amount + $taxAmount;
    }

    // Stripe決済作成（実際の実装ではStripe SDKを使用）
    // Stripe::setApiKey(STRIPE_SECRET_KEY);
    
    // ここでは簡易版として決済レコードを作成
    $stmt = $db->prepare("
        INSERT INTO payments (user_id, business_card_id, payment_type, amount, tax_amount, total_amount, payment_method, payment_status)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
    ");
    
    $stmt->execute([
        $userId,
        $userInfo['business_card_id'],
        $paymentType,
        $amount,
        $taxAmount,
        $totalAmount,
        $paymentMethod
    ]);

    $paymentId = $db->lastInsertId();

    // Stripe PaymentIntent作成（実際の実装）
    /*
    if ($paymentMethod === 'credit_card') {
        $paymentIntent = PaymentIntent::create([
            'amount' => (int)($totalAmount * 100), // 円単位に変換
            'currency' => 'jpy',
            'metadata' => [
                'user_id' => $userId,
                'payment_id' => $paymentId,
                'payment_type' => $paymentType
            ]
        ]);

        $stmt = $db->prepare("UPDATE payments SET stripe_payment_intent_id = ? WHERE id = ?");
        $stmt->execute([$paymentIntent->id, $paymentId]);
    }
    */

    // 新規ユーザーの場合、サブスクリプションも作成
    if ($paymentType === 'new' && $paymentMethod === 'credit_card') {
        // Stripe Subscription作成（実際の実装）
        /*
        $subscription = Subscription::create([
            'customer' => $customerId,
            'items' => [[
                'price_data' => [
                    'currency' => 'jpy',
                    'product' => $productId,
                    'recurring' => ['interval' => 'month'],
                    'unit_amount' => (int)($monthlyAmount * 100),
                ],
            ]],
        ]);
        */
    }

    sendSuccessResponse([
        'payment_id' => $paymentId,
        'amount' => $amount,
        'tax_amount' => $taxAmount,
        'total_amount' => $totalAmount,
        'payment_method' => $paymentMethod,
        // 'client_secret' => $paymentIntent->client_secret ?? null,
        'message' => '決済処理を開始しました'
    ], '決済処理を開始しました');

} catch (Exception $e) {
    error_log("Create Payment Intent Error: " . $e->getMessage());
    sendErrorResponse('サーバーエラーが発生しました', 500);
}

