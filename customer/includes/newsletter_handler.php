<?php
/**
 * Newsletter Subscription Handler
 * Handles newsletter email subscriptions
 */

header('Content-Type: application/json');

require_once '../../config/db_connection.php';

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get and validate email
$email = isset($_POST['email']) ? sanitizeInput($_POST['email']) : '';

if (empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Email is required']);
    exit;
}

if (!validateEmail($email)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address']);
    exit;
}

try {
    // Check if email already exists
    $existingSubscriber = fetchRow("SELECT id FROM newsletter_subscribers WHERE email = ?", [$email]);
    
    if ($existingSubscriber) {
        echo json_encode(['success' => false, 'message' => 'This email is already subscribed']);
        exit;
    }
    
    // Insert new subscriber
    $data = [
        'email' => $email,
        'subscribed_at' => date('Y-m-d H:i:s'),
        'status' => 'active',
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ];
    
    $subscriberId = insertData('newsletter_subscribers', $data);
    
    if ($subscriberId) {
        // Log the subscription
        error_log("New newsletter subscription: $email");
        
        echo json_encode([
            'success' => true, 
            'message' => 'Thank you for subscribing! You will receive our latest updates.'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to subscribe. Please try again.']);
    }
    
} catch (Exception $e) {
    error_log("Newsletter subscription error: " . $e->getMessage());
    
    // Check if table doesn't exist
    if (strpos($e->getMessage(), "doesn't exist") !== false) {
        // Create the table
        try {
            global $pdo;
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS newsletter_subscribers (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    email VARCHAR(255) NOT NULL UNIQUE,
                    subscribed_at DATETIME NOT NULL,
                    status ENUM('active', 'unsubscribed') DEFAULT 'active',
                    ip_address VARCHAR(45),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_email (email),
                    INDEX idx_status (status)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            
            // Try inserting again
            $data = [
                'email' => $email,
                'subscribed_at' => date('Y-m-d H:i:s'),
                'status' => 'active',
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ];
            
            $subscriberId = insertData('newsletter_subscribers', $data);
            
            if ($subscriberId) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Thank you for subscribing! You will receive our latest updates.'
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to subscribe. Please try again.']);
            }
        } catch (Exception $e2) {
            error_log("Failed to create newsletter_subscribers table: " . $e2->getMessage());
            echo json_encode(['success' => false, 'message' => 'Database error. Please contact support.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again later.']);
    }
}
?>
