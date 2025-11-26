<?php
// すべての不要な出力を防ぐ
ini_set('display_errors', 0);
error_reporting(E_ALL);
ob_start();

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../middleware/auth.php';

header('Content-Type: application/json; charset=UTF-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendErrorResponse('Method not allowed', 405);
    }

    $userId = requireAuth();

    if (empty($_FILES['file'])) {
        sendErrorResponse('ファイルがアップロードされていません', 400);
    }

    $fileType = $_POST['file_type'] ?? 'photo';
    $file = $_FILES['file'];

    // アップロード処理
    $uploadResult = uploadFile($file, $fileType . '/');

    if (!$uploadResult['success']) {
        sendErrorResponse($uploadResult['message'], 400);
    }

    // free 以外は DB に保存
    if ($fileType !== 'free') {
        $database = new Database();
        $db = $database->getConnection();

        $fieldName = ($fileType === 'logo') ? 'company_logo' : 'profile_photo';

        $stmt = $db->prepare("UPDATE business_cards SET $fieldName = ? WHERE user_id = ?");
        $stmt->execute([$uploadResult['file_path'], $userId]);
    }

    // 出力バッファクリア → warning/notice が混入しない
    ob_clean();

    sendSuccessResponse([
        'file_path' => BASE_URL . '/' . $uploadResult['file_path'],
        'file_name' => $uploadResult['file_name']
    ], 'ファイルをアップロードしました');

} catch (Exception $e) {
    error_log("Upload Error: " . $e->getMessage());
    ob_clean();
    sendErrorResponse('サーバーエラーが発生しました', 500);
}
