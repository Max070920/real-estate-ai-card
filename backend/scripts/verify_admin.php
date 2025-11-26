<?php
/**
 * Verify Admin Account
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$email = 'admin@rchukai.jp';
$password = 'admin123';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $stmt = $db->prepare("SELECT * FROM admins WHERE email = ?");
    $stmt->execute([$email]);
    $admin = $stmt->fetch();
    
    if ($admin) {
        echo "✓ Admin account found!\n";
        echo "Email: {$admin['email']}\n";
        echo "Role: {$admin['role']}\n";
        echo "Password hash: {$admin['password_hash']}\n";
        
        // Verify password
        if (verifyPassword($password, $admin['password_hash'])) {
            echo "✓ Password verification: SUCCESS\n";
        } else {
            echo "✗ Password verification: FAILED\n";
        }
    } else {
        echo "✗ Admin account not found!\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

