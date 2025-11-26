<?php
/**
 * Update Admin Password Script
 * Run this script to update the admin password to "admin123"
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$email = 'admin@rchukai.jp';
$password = 'admin123';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Generate password hash
    $passwordHash = hashPassword($password);
    
    // Check if admin exists
    $stmt = $db->prepare("SELECT id FROM admins WHERE email = ?");
    $stmt->execute([$email]);
    $admin = $stmt->fetch();
    
    if ($admin) {
        // Update existing admin
        $stmt = $db->prepare("
            UPDATE admins 
            SET password_hash = ?, 
                last_password_change = NOW(),
                updated_at = NOW()
            WHERE email = ?
        ");
        $result = $stmt->execute([$passwordHash, $email]);
        
        if ($result) {
            echo "✓ Admin password updated successfully!\n";
            echo "Email: {$email}\n";
            echo "Password: {$password}\n";
            echo "Hash: {$passwordHash}\n";
        } else {
            echo "✗ Failed to update password\n";
        }
    } else {
        // Create new admin if doesn't exist
        $stmt = $db->prepare("
            INSERT INTO admins (email, password_hash, role, last_password_change) 
            VALUES (?, ?, 'admin', NOW())
        ");
        $result = $stmt->execute([$email, $passwordHash]);
        
        if ($result) {
            echo "✓ Admin account created successfully!\n";
            echo "Email: {$email}\n";
            echo "Password: {$password}\n";
            echo "Hash: {$passwordHash}\n";
        } else {
            echo "✗ Failed to create admin account\n";
        }
    }
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}

