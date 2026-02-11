<?php
/**
 * ORDIVO - Image Management System
 * Comprehensive image upload and management for super admin
 */

require_once '../config/db_connection.php';

// Debug: Check if session is working
if (!isset($_SESSION)) {
    die('Session not started. Please check db_connection.php');
}

// Check if user is logged in and is super admin
if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'super_admin') {
    if (isset($_POST['ajax_action'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
        exit;
    }
    header('Location: ../auth/login.php');
    exit;
}

// Create necessary tables if they don't exist
function createImageTables() {
    global $pdo;
    try {
        // Create site_images table
        $pdo->exec("CREATE TABLE IF NOT EXISTS `site_images` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `image_type` varchar(50) NOT NULL COMMENT 'logo, hero, background, favicon, etc.',
            `image_name` varchar(255) NOT NULL,
            `image_path` varchar(500) NOT NULL,
            `alt_text` varchar(255) DEFAULT NULL,
            `width` int DEFAULT NULL,
            `height` int DEFAULT NULL,
            `file_size` int DEFAULT NULL,
            `mime_type` varchar(100) DEFAULT NULL,
            `is_active` tinyint(1) DEFAULT 1,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `image_type` (`image_type`),
            KEY `is_active` (`is_active`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        // Create admin_permissions table
        $pdo->exec("CREATE TABLE IF NOT EXISTS `admin_permissions` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `permission_type` varchar(100) NOT NULL,
            `permission_value` text DEFAULT NULL,
            `is_granted` tinyint(1) DEFAULT 1,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `user_id` (`user_id`),
            KEY `permission_type` (`permission_type`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        // Add missing columns to site_settings if they don't exist
        $columns = [
            'hero_background_image' => "ALTER TABLE site_settings ADD COLUMN hero_background_image VARCHAR(255) DEFAULT NULL AFTER logo_url",
            'hero_title' => "ALTER TABLE site_settings ADD COLUMN hero_title VARCHAR(255) DEFAULT 'Welcome to ORDIVO' AFTER hero_background_image",
            'hero_subtitle' => "ALTER TABLE site_settings ADD COLUMN hero_subtitle TEXT DEFAULT 'Fast Delivery • Fresh Products • Best Prices' AFTER hero_title",
            'hero_button_text' => "ALTER TABLE site_settings ADD COLUMN hero_button_text VARCHAR(100) DEFAULT 'Order Now' AFTER hero_subtitle",
            'hero_button_link' => "ALTER TABLE site_settings ADD COLUMN hero_button_link VARCHAR(255) DEFAULT '#' AFTER hero_button_text",
            'favicon_url' => "ALTER TABLE site_settings ADD COLUMN favicon_url VARCHAR(255) DEFAULT NULL AFTER hero_button_link",
            'background_pattern' => "ALTER TABLE site_settings ADD COLUMN background_pattern VARCHAR(50) DEFAULT 'none' AFTER favicon_url"
        ];
        
        foreach ($columns as $column => $sql) {
            try {
                $pdo->exec($sql);
            } catch (Exception $e) {
                // Column might already exist, continue
            }
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Error creating image tables: " . $e->getMessage());
        return false;
    }
}

// Handle image upload
function handleImageUpload($file, $imageType, $altText = '') {
    try {
        // Create uploads directory if it doesn't exist
        $uploadDir = '../uploads/images/';
        if (!file_exists($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                throw new Exception('Failed to create upload directory.');
            }
        }
        
        // Validate file
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($file['type'], $allowedTypes)) {
            throw new Exception('Invalid file type. Only JPEG, PNG, GIF, WebP, and SVG files are allowed.');
        }
        
        if ($file['size'] > $maxSize) {
            throw new Exception('File size too large. Maximum size is 5MB.');
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = $imageType . '_' . time() . '_' . uniqid() . '.' . $extension;
        $filepath = $uploadDir . $filename;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            throw new Exception('Failed to upload file.');
        }
        
        // Get image dimensions
        $width = null;
        $height = null;
        if (function_exists('getimagesize')) {
            $imageInfo = getimagesize($filepath);
            if ($imageInfo !== false) {
                $width = $imageInfo[0];
                $height = $imageInfo[1];
            }
        }
        
        // Save to database
        $imageData = [
            'image_type' => $imageType,
            'image_name' => $file['name'],
            'image_path' => 'uploads/images/' . $filename,
            'alt_text' => $altText,
            'width' => $width,
            'height' => $height,
            'file_size' => $file['size'],
            'mime_type' => $file['type'],
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        insertData('site_images', $imageData);
        
        return 'uploads/images/' . $filename;
    } catch (Exception $e) {
        throw new Exception('Image upload failed: ' . $e->getMessage());
    }
}

// Get images by type
function getImagesByType($imageType) {
    try {
        return fetchAll("SELECT * FROM site_images WHERE image_type = ? AND is_active = 1 ORDER BY created_at DESC", [$imageType]);
    } catch (Exception $e) {
        return [];
    }
}

// Delete image
function deleteImage($imageId) {
    try {
        $image = fetchRow("SELECT * FROM site_images WHERE id = ?", [$imageId]);
        if ($image) {
            // Delete file
            $filepath = '../' . $image['image_path'];
            if (file_exists($filepath)) {
                unlink($filepath);
            }
            
            // Delete from database
            deleteData('site_images', 'id = ?', [$imageId]);
            return true;
        }
        return false;
    } catch (Exception $e) {
        return false;
    }
}

// Initialize tables
createImageTables();

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['ajax_action']) {
        case 'upload_image':
            try {
                if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                    throw new Exception('No file uploaded or upload error.');
                }
                
                $imageType = sanitizeInput($_POST['image_type'] ?? '');
                $altText = sanitizeInput($_POST['alt_text'] ?? '');
                
                $imagePath = handleImageUpload($_FILES['image'], $imageType, $altText);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Image uploaded successfully!',
                    'image_path' => $imagePath
                ]);
            } catch (Exception $e) {
                echo json_encode([
                    'success' => false,
                    'message' => $e->getMessage()
                ]);
            }
            exit;
            
        case 'delete_image':
            try {
                $imageId = (int)($_POST['image_id'] ?? 0);
                if (deleteImage($imageId)) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Image deleted successfully!'
                    ]);
                } else {
                    throw new Exception('Failed to delete image.');
                }
            } catch (Exception $e) {
                echo json_encode([
                    'success' => false,
                    'message' => $e->getMessage()
                ]);
            }
            exit;
            
        case 'get_images':
            try {
                $imageType = sanitizeInput($_POST['image_type'] ?? '');
                if ($imageType === 'all') {
                    $images = fetchAll("SELECT * FROM site_images WHERE is_active = 1 ORDER BY created_at DESC");
                } else {
                    $images = getImagesByType($imageType);
                }
                
                echo json_encode([
                    'success' => true,
                    'images' => $images
                ]);
            } catch (Exception $e) {
                echo json_encode([
                    'success' => false,
                    'message' => $e->getMessage()
                ]);
            }
            exit;
            
        case 'update_field':
            try {
                $table = sanitizeInput($_POST['table'] ?? '');
                $field = sanitizeInput($_POST['field'] ?? '');
                $recordId = (int)($_POST['record_id'] ?? 0);
                $value = $_POST['value'] ?? '';
                
                // Validate table and field names to prevent SQL injection
                $allowedTables = ['site_settings', 'contact_settings', 'layout_settings', 'users', 'vendors', 'categories'];
                if (!in_array($table, $allowedTables)) {
                    throw new Exception('Invalid table name.');
                }
                
                // Update the field
                updateData($table, [$field => $value], 'id = ?', [$recordId]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Field updated successfully!'
                ]);
            } catch (Exception $e) {
                echo json_encode([
                    'success' => false,
                    'message' => $e->getMessage()
                ]);
            }
            exit;
            
        case 'save_custom_code':
            try {
                $customCss = $_POST['custom_css'] ?? '';
                $customJs = $_POST['custom_js'] ?? '';
                
                // Create custom code files
                $cssDir = '../uploads/custom/';
                if (!file_exists($cssDir)) {
                    mkdir($cssDir, 0755, true);
                }
                
                file_put_contents($cssDir . 'custom.css', $customCss);
                file_put_contents($cssDir . 'custom.js', $customJs);
                
                // Save to database
                try {
                    $customExists = fetchValue("SELECT COUNT(*) FROM custom_code");
                    $codeData = [
                        'custom_css' => $customCss,
                        'custom_js' => $customJs,
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                    
                    if ($customExists) {
                        updateData('custom_code', $codeData, 'id = 1');
                    } else {
                        $codeData['created_at'] = date('Y-m-d H:i:s');
                        insertData('custom_code', $codeData);
                    }
                } catch (Exception $e) {
                    // Create custom_code table if it doesn't exist
                    global $pdo;
                    $pdo->exec("CREATE TABLE IF NOT EXISTS `custom_code` (
                        `id` int(11) NOT NULL AUTO_INCREMENT,
                        `custom_css` longtext DEFAULT NULL,
                        `custom_js` longtext DEFAULT NULL,
                        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                        `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                        PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                    
                    $codeData['created_at'] = date('Y-m-d H:i:s');
                    insertData('custom_code', $codeData);
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Custom code saved successfully!'
                ]);
            } catch (Exception $e) {
                echo json_encode([
                    'success' => false,
                    'message' => $e->getMessage()
                ]);
            }
            exit;
    }
}
?>