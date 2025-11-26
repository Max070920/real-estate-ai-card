<?php
/**
 * Real Estate License Lookup API
 * Note: This is a placeholder - actual implementation would require
 * scraping or API access to 国交省宅建業者検索システム
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json; charset=UTF-8');

try {
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method !== 'GET' && $method !== 'POST') {
        sendErrorResponse('Method not allowed', 405);
    }

    $prefecture = $_GET['prefecture'] ?? $_POST['prefecture'] ?? '';
    $renewal = $_GET['renewal'] ?? $_POST['renewal'] ?? '';
    $registration = $_GET['registration'] ?? $_POST['registration'] ?? '';
    
    if (empty($prefecture) || empty($renewal) || empty($registration)) {
        sendErrorResponse('都道府県、更新番号、登録番号をすべて入力してください', 400);
    }

    // Note: Actual implementation would require:
    // 1. Access to 国交省宅建業者検索システム API (if available)
    // 2. Or web scraping (requires careful handling of terms of service)
    // 3. Or maintaining a local database
    
    // For now, return a placeholder response
    // In production, this should be implemented to actually query the system
    
    // Example: If you have API access or database:
    /*
    $database = new Database();
    $db = $database->getConnection();
    
    $stmt = $db->prepare("
        SELECT company_name, address 
        FROM real_estate_licenses 
        WHERE prefecture = ? AND renewal_number = ? AND registration_number = ?
    ");
    $stmt->execute([$prefecture, $renewal, $registration]);
    $result = $stmt->fetch();
    
    if ($result) {
        sendSuccessResponse([
            'company_name' => $result['company_name'],
            'address' => $result['address']
        ], '会社情報を取得しました');
    } else {
        sendErrorResponse('該当する宅建業者情報が見つかりませんでした', 404);
    }
    */
    
    // Placeholder response
    sendErrorResponse('宅建業者検索機能は現在実装中です。手動で住所を入力してください。', 501);

} catch (Exception $e) {
    error_log("License Lookup Error: " . $e->getMessage());
    sendErrorResponse('サーバーエラーが発生しました', 500);
}

