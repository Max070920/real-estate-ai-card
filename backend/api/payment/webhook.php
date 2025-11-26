<?php
/**
 * Stripe Webhook Handler
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json; charset=UTF-8');

try {
    $payload = @file_get_contents('php://input');
    $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
    $endpoint_secret = STRIPE_WEBHOOK_SECRET;

    // Webhook署名検証（実際の実装ではStripe SDKを使用）
    // $event = \Stripe\Webhook::constructEvent($payload, $sig_header, $endpoint_secret);

    $event = json_decode($payload, true);

    $database = new Database();
    $db = $database->getConnection();

    switch ($event['type']) {
        case 'payment_intent.succeeded':
            $paymentIntent = $event['data']['object'];
            $paymentIntentId = $paymentIntent['id'];
            
            // 決済ステータス更新
            $stmt = $db->prepare("
                UPDATE payments 
                SET payment_status = 'completed', paid_at = NOW()
                WHERE stripe_payment_intent_id = ?
            ");
            $stmt->execute([$paymentIntentId]);
            
            // 決済完了後の処理（QRコード発行など）
            $stmt = $db->prepare("
                SELECT p.user_id, p.business_card_id
                FROM payments p
                WHERE p.stripe_payment_intent_id = ? AND p.payment_status = 'completed'
            ");
            $stmt->execute([$paymentIntentId]);
            $payment = $stmt->fetch();
            
            if ($payment) {
                // QRコード発行処理をトリガー
                // QRコード発行APIを呼び出す
            }
            
            break;

        case 'payment_intent.payment_failed':
            $paymentIntent = $event['data']['object'];
            $paymentIntentId = $paymentIntent['id'];
            
            $stmt = $db->prepare("
                UPDATE payments 
                SET payment_status = 'failed'
                WHERE stripe_payment_intent_id = ?
            ");
            $stmt->execute([$paymentIntentId]);
            break;

        case 'customer.subscription.created':
        case 'customer.subscription.updated':
            $subscription = $event['data']['object'];
            $subscriptionId = $subscription['id'];
            $customerId = $subscription['customer'];
            
            // サブスクリプション情報を保存/更新
            break;

        case 'customer.subscription.deleted':
            $subscription = $event['data']['object'];
            $subscriptionId = $subscription['id'];
            
            $stmt = $db->prepare("
                UPDATE subscriptions 
                SET status = 'cancelled', cancelled_at = NOW()
                WHERE stripe_subscription_id = ?
            ");
            $stmt->execute([$subscriptionId]);
            break;
    }

    sendSuccessResponse([], 'Webhook processed');

} catch (Exception $e) {
    error_log("Stripe Webhook Error: " . $e->getMessage());
    http_response_code(400);
    sendErrorResponse('Webhook processing failed', 400);
}

