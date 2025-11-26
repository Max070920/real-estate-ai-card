<?php
/**
 * Update Business Card API
 */
// Start output buffering to prevent any output before JSON
ob_start();

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../middleware/auth.php';

// Set error reporting to catch issues
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors, log them instead

// Clear any output that might have been generated
ob_clean();

header('Content-Type: application/json; charset=UTF-8');

try {
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method !== 'POST' && $method !== 'PUT') {
        sendErrorResponse('Method not allowed', 405);
    }

    $userId = requireAuth();

    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON decode error: " . json_last_error_msg() . " - Input: " . substr($rawInput, 0, 500));
        sendErrorResponse('Invalid JSON data: ' . json_last_error_msg(), 400);
    }
    
    if (!$input) {
        $input = $_POST;
    }
    
    error_log("Update request - User ID: $userId, Input keys: " . implode(', ', array_keys($input)));

    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        error_log("Database connection failed");
        sendErrorResponse('データベース接続に失敗しました', 500);
    }

    // 既存のビジネスカード取得
    $stmt = $db->prepare("SELECT id FROM business_cards WHERE user_id = ?");
    if (!$stmt) {
        error_log("Prepare failed: " . implode(', ', $db->errorInfo()));
        sendErrorResponse('データベースエラーが発生しました', 500);
    }
    
    $stmt->execute([$userId]);
    $businessCard = $stmt->fetch();

    if (!$businessCard) {
        sendErrorResponse('ビジネスカードが見つかりません', 404);
    }

    $bcId = $businessCard['id'];
    error_log("Updating business card ID: $bcId for user ID: $userId");

    // トランザクション開始
    if (!$db->beginTransaction()) {
        error_log("Failed to begin transaction");
        sendErrorResponse('トランザクションの開始に失敗しました', 500);
    }

    try {
        // 基本情報更新
        $updateFields = [];
        $updateValues = [];

        $fields = [
            'company_name', 'company_logo', 'profile_photo',
            'real_estate_license_prefecture', 'real_estate_license_renewal_number', 
            'real_estate_license_registration_number', 'company_postal_code', 
            'company_address', 'company_phone', 'company_website',
            'branch_department', 'position', 'name', 'name_romaji', 'mobile_phone',
            'birth_date', 'current_residence', 'hometown', 'alma_mater',
            'qualifications', 'hobbies', 'free_input'
        ];

        foreach ($fields as $field) {
            if (isset($input[$field])) {
                $value = $input[$field];
                
                // Handle empty values - convert to NULL for optional fields
                if ($value === '' || $value === null) {
                    // For required fields like 'name' and 'mobile_phone', skip if empty
                    // For optional fields, set to NULL
                    if (in_array($field, ['name', 'mobile_phone'])) {
                        continue; // Skip required fields if empty
                    }
                    $updateFields[] = "$field = ?";
                    $updateValues[] = null;
                } else {
                    // For TEXT fields like free_input, don't sanitize JSON strings
                    if ($field === 'free_input' && is_string($value)) {
                        // Validate it's valid JSON
                        $decoded = json_decode($value, true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $updateFields[] = "$field = ?";
                            $updateValues[] = $value; // Keep as JSON string
                        } else {
                            // Invalid JSON, sanitize as regular text
                            $updateFields[] = "$field = ?";
                            $updateValues[] = sanitizeInput($value);
                        }
                    } else {
                        $updateFields[] = "$field = ?";
                        $updateValues[] = sanitizeInput($value);
                    }
                }
            }
        }

        if (!empty($updateFields)) {
            $updateValues[] = $bcId;
            $sql = "UPDATE business_cards SET " . implode(', ', $updateFields) . " WHERE id = ?";
            error_log("Update SQL: " . $sql);
            error_log("Update values count: " . count($updateValues));
            
            try {
                $stmt = $db->prepare($sql);
                if (!$stmt) {
                    $errorInfo = $db->errorInfo();
                    error_log("SQL Prepare error: " . ($errorInfo[2] ?? 'Unknown error'));
                    throw new Exception("SQL prepare failed");
                }
                $result = $stmt->execute($updateValues);
                if (!$result) {
                    $errorInfo = $stmt->errorInfo();
                    error_log("SQL Execute error: " . ($errorInfo[2] ?? 'Unknown error'));
                    throw new Exception("SQL execute failed: " . ($errorInfo[2] ?? 'Unknown error'));
                }
                error_log("Update successful for business card ID: $bcId");
            } catch (PDOException $e) {
                error_log("PDO Exception: " . $e->getMessage());
                throw new Exception("Database error: " . $e->getMessage());
            }
        } else {
            error_log("No fields to update");
        }

        // 挨拶文の更新
        if (isset($input['greetings']) && is_array($input['greetings'])) {
            // 既存の挨拶文を削除
            $stmt = $db->prepare("DELETE FROM greeting_messages WHERE business_card_id = ?");
            $stmt->execute([$bcId]);

            // 新しい挨拶文を挿入
            $stmt = $db->prepare("
                INSERT INTO greeting_messages (business_card_id, title, content, display_order)
                VALUES (?, ?, ?, ?)
            ");

            foreach ($input['greetings'] as $order => $greeting) {
                if (!empty($greeting['title']) || !empty($greeting['content'])) {
                    $stmt->execute([
                        $bcId,
                        sanitizeInput($greeting['title'] ?? ''),
                        sanitizeInput($greeting['content'] ?? ''),
                        $order
                    ]);
                }
            }
        }

        // テックツールの更新
        if (isset($input['tech_tools']) && is_array($input['tech_tools'])) {
            // 既存のテックツールを削除
            $stmt = $db->prepare("DELETE FROM tech_tool_selections WHERE business_card_id = ?");
            $stmt->execute([$bcId]);

            // 新しいテックツールを挿入
            $stmt = $db->prepare("
                INSERT INTO tech_tool_selections (business_card_id, tool_type, tool_url, display_order, is_active)
                VALUES (?, ?, ?, ?, ?)
            ");

            foreach ($input['tech_tools'] as $order => $tool) {
                if (!empty($tool['tool_type'])) {
                    $stmt->execute([
                        $bcId,
                        sanitizeInput($tool['tool_type']),
                        sanitizeInput($tool['tool_url'] ?? ''),
                        $order,
                        isset($tool['is_active']) ? (int)$tool['is_active'] : 1
                    ]);
                }
            }
        }

        // コミュニケーション方法の更新
        if (isset($input['communication_methods']) && is_array($input['communication_methods'])) {
            // 既存のコミュニケーション方法を削除
            $stmt = $db->prepare("DELETE FROM communication_methods WHERE business_card_id = ?");
            $stmt->execute([$bcId]);

            // 新しいコミュニケーション方法を挿入
            $stmt = $db->prepare("
                INSERT INTO communication_methods (business_card_id, method_type, method_name, method_url, method_id, is_active, display_order)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            foreach ($input['communication_methods'] as $order => $method) {
                if (!empty($method['method_type'])) {
                    $stmt->execute([
                        $bcId,
                        sanitizeInput($method['method_type']),
                        sanitizeInput($method['method_name'] ?? ''),
                        sanitizeInput($method['method_url'] ?? ''),
                        sanitizeInput($method['method_id'] ?? ''),
                        isset($method['is_active']) ? (int)$method['is_active'] : 1,
                        $order
                    ]);
                }
            }
        }

        // トランザクションコミット
        $db->commit();

        sendSuccessResponse([
            'business_card_id' => $bcId
        ], 'ビジネスカードを更新しました');

    } catch (Exception $e) {
        // Only rollback if transaction is active
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log("Transaction error: " . $e->getMessage());
        throw $e;
    }

} catch (Exception $e) {
    error_log("Update Business Card Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Make sure we send JSON even on error
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=UTF-8');
    }
    
    // Don't expose internal error details in production
    $errorMessage = ENVIRONMENT === 'development' 
        ? $e->getMessage() 
        : 'サーバーエラーが発生しました。ログを確認してください。';
    
    sendErrorResponse($errorMessage, 500);
}

