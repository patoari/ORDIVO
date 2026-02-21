<?php
/**
 * ORDIVO - Vendor Settings
 * Vendor profile and business settings management
 */

require_once '../config/db_connection.php';

// Check if user is logged in and is vendor
if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'vendor') {
    header('Location: ../auth/login.php');
    exit;
}

$vendorId = $_SESSION['user_id'];

// Get vendor business information
try {
    global $pdo;
    $stmt = $pdo->prepare("SELECT v.name, v.logo FROM vendors v WHERE v.owner_id = ? LIMIT 1");
    $stmt->execute([$vendorId]);
    $vendorInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $vendorBusinessName = $vendorInfo['name'] ?? 'My Business';
    $vendorLogo = $vendorInfo['logo'] ?? '';
    
    // Fix logo path for vendor directory - add ../ prefix if it's a relative path
    if (!empty($vendorLogo) && $vendorLogo !== '🍔' && $vendorLogo !== '🍽️') {
        if (strpos($vendorLogo, 'uploads/') === 0) {
            $vendorLogo = '../' . $vendorLogo;
        }
        elseif (!preg_match('/^(https?:\/\/|\.\.\/|\/)/i', $vendorLogo)) {
            $vendorLogo = '../' . $vendorLogo;
        }
    }
} catch (Exception $e) {
    error_log("Error loading vendor info in vendor settings: " . $e->getMessage());
    $vendorBusinessName = 'My Business';
    $vendorLogo = '';
}

// Handle file uploads
function handleImageUpload($file, $category, $userId) {
    $uploadDir = '../uploads/vendors/';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            throw new Exception('Failed to create upload directory. Please check permissions.');
        }
    }
    
    // Check if directory is writable
    if (!is_writable($uploadDir)) {
        throw new Exception('Upload directory is not writable. Please check permissions.');
    }
    
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $allowedMimeTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    // Validate file type by MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedMimeTypes) && !in_array($file['type'], $allowedTypes)) {
        throw new Exception('Invalid file type. Only JPEG, PNG, GIF, and WebP images are allowed. Detected type: ' . $mimeType);
    }
    
    if ($file['size'] > $maxSize) {
        $sizeMB = round($file['size'] / (1024 * 1024), 2);
        throw new Exception("File size too large ({$sizeMB}MB). Maximum 5MB allowed.");
    }
    
    if ($file['size'] == 0) {
        throw new Exception('File appears to be empty. Please select a valid image file.');
    }
    
    // Validate that it's actually an image
    $imageInfo = getimagesize($file['tmp_name']);
    if (!$imageInfo) {
        throw new Exception('File is not a valid image. Please select a proper image file.');
    }
    
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (empty($extension)) {
        // Try to determine extension from MIME type
        switch ($mimeType) {
            case 'image/jpeg':
                $extension = 'jpg';
                break;
            case 'image/png':
                $extension = 'png';
                break;
            case 'image/gif':
                $extension = 'gif';
                break;
            case 'image/webp':
                $extension = 'webp';
                break;
            default:
                $extension = 'jpg';
        }
    }
    
    $fileName = $category . '_' . $userId . '_' . time() . '.' . $extension;
    $filePath = $uploadDir . $fileName;
    $relativePath = 'uploads/vendors/' . $fileName;
    
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        throw new Exception('Failed to save uploaded file. Please check server permissions.');
    }
    
    // Verify the file was actually saved
    if (!file_exists($filePath)) {
        throw new Exception('File upload completed but file not found. Please try again.');
    }
    
    // Create thumbnail for profile images only (not for logos or covers)
    if ($category === 'profile' && extension_loaded('gd')) {
        try {
            createThumbnail($filePath, $uploadDir . 'thumb_' . $fileName, 200, 200);
        } catch (Exception $e) {
            // Thumbnail creation failed, but continue with main upload
            error_log("Thumbnail creation failed: " . $e->getMessage());
        }
    }
    
    return $relativePath;
}

function createThumbnail($source, $destination, $width, $height) {
    // Check if GD extension is loaded
    if (!extension_loaded('gd')) {
        throw new Exception('GD extension is not available for thumbnail creation.');
    }
    
    $imageInfo = getimagesize($source);
    if (!$imageInfo) {
        throw new Exception('Invalid image file for thumbnail creation.');
    }
    
    $sourceWidth = $imageInfo[0];
    $sourceHeight = $imageInfo[1];
    $sourceType = $imageInfo[2];
    
    $sourceImage = null;
    
    switch ($sourceType) {
        case IMAGETYPE_JPEG:
            $sourceImage = imagecreatefromjpeg($source);
            break;
        case IMAGETYPE_PNG:
            $sourceImage = imagecreatefrompng($source);
            break;
        case IMAGETYPE_GIF:
            $sourceImage = imagecreatefromgif($source);
            break;
        default:
            throw new Exception('Unsupported image type for thumbnail creation.');
    }
    
    if (!$sourceImage) {
        throw new Exception('Failed to create image resource.');
    }
    
    $thumbnail = imagecreatetruecolor($width, $height);
    
    // Handle transparency for PNG and GIF
    if ($sourceType == IMAGETYPE_PNG || $sourceType == IMAGETYPE_GIF) {
        imagealphablending($thumbnail, false);
        imagesavealpha($thumbnail, true);
        $transparent = imagecolorallocatealpha($thumbnail, 255, 255, 255, 127);
        imagefilledrectangle($thumbnail, 0, 0, $width, $height, $transparent);
    }
    
    imagecopyresampled($thumbnail, $sourceImage, 0, 0, 0, 0, $width, $height, $sourceWidth, $sourceHeight);
    
    $success = false;
    switch ($sourceType) {
        case IMAGETYPE_JPEG:
            $success = imagejpeg($thumbnail, $destination, 90);
            break;
        case IMAGETYPE_PNG:
            $success = imagepng($thumbnail, $destination);
            break;
        case IMAGETYPE_GIF:
            $success = imagegif($thumbnail, $destination);
            break;
    }
    
    imagedestroy($sourceImage);
    imagedestroy($thumbnail);
    
    if (!$success) {
        throw new Exception('Failed to save thumbnail.');
    }
    
    return true;
}

// Handle settings updates
$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'update_profile':
            try {
                $data = [
                    'name' => sanitizeInput($_POST['name']),
                    'email' => sanitizeInput($_POST['email']),
                    'phone' => sanitizeInput($_POST['phone']),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                updateData('users', $data, "id = ?", [$vendorId]);
                
                // Update session
                $_SESSION['user_name'] = $data['name'];
                $_SESSION['user_email'] = $data['email'];
                
                $success = 'Profile updated successfully!';
            } catch (Exception $e) {
                $error = 'Failed to update profile: ' . $e->getMessage();
            }
            break;
            
        case 'change_password':
            try {
                $currentPassword = $_POST['current_password'];
                $newPassword = $_POST['new_password'];
                $confirmPassword = $_POST['confirm_password'];
                
                // Verify current password
                $user = fetchRow("SELECT password FROM users WHERE id = ?", [$vendorId]);
                if (!password_verify($currentPassword, $user['password'])) {
                    $error = 'Current password is incorrect.';
                    break;
                }
                
                // Validate new password
                if ($newPassword !== $confirmPassword) {
                    $error = 'New passwords do not match.';
                    break;
                }
                
                if (strlen($newPassword) < 6) {
                    $error = 'New password must be at least 6 characters long.';
                    break;
                }
                
                // Update password
                updateData('users', 
                    ['password' => password_hash($newPassword, PASSWORD_DEFAULT)], 
                    "id = ?", 
                    [$vendorId]
                );
                
                $success = 'Password changed successfully!';
            } catch (Exception $e) {
                $error = 'Failed to change password: ' . $e->getMessage();
            }
            break;
            
        case 'update_business':
            try {
                global $pdo;
                
                // Get the vendor record ID
                $vendorRecord = fetchRow("SELECT id FROM vendors WHERE owner_id = ?", [$vendorId]);
                
                if (!$vendorRecord) {
                    throw new Exception('Vendor record not found. Please contact support.');
                }
                
                $actualVendorId = $vendorRecord['id'];
                
                // Handle logo upload if provided (use 'logo' category, not 'profile')
                $logoPath = null;
                if (isset($_FILES['business_logo']) && $_FILES['business_logo']['error'] === UPLOAD_ERR_OK) {
                    try {
                        $logoPath = handleImageUpload($_FILES['business_logo'], 'logo', $vendorId);
                    } catch (Exception $e) {
                        error_log("Logo upload error: " . $e->getMessage());
                        // Continue with other updates even if logo upload fails
                    }
                }
                
                // Update the vendors table (this is what displays in the sidebar)
                $vendorData = [
                    'name' => sanitizeInput($_POST['business_name']),
                    'address' => sanitizeInput($_POST['business_address']),
                    'phone' => sanitizeInput($_POST['business_phone']),
                    'email' => sanitizeInput($_POST['business_email']),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                // Add logo to update if uploaded
                if ($logoPath) {
                    $vendorData['logo'] = $logoPath;
                }
                
                updateData('vendors', $vendorData, "id = ?", [$actualVendorId]);
                
                // Also update vendor_settings table for additional settings
                $pdo->exec("CREATE TABLE IF NOT EXISTS vendor_settings (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    vendor_id INT NOT NULL,
                    business_name VARCHAR(255),
                    business_address TEXT,
                    business_phone VARCHAR(20),
                    business_email VARCHAR(255),
                    operating_hours TEXT,
                    delivery_radius INT DEFAULT 5,
                    min_order_amount DECIMAL(10,2) DEFAULT 0,
                    delivery_fee DECIMAL(10,2) DEFAULT 0,
                    tax_rate DECIMAL(5,2) DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_vendor (vendor_id)
                )");
                
                $settingsData = [
                    'vendor_id' => $actualVendorId,
                    'business_name' => sanitizeInput($_POST['business_name']),
                    'business_address' => sanitizeInput($_POST['business_address']),
                    'business_phone' => sanitizeInput($_POST['business_phone']),
                    'business_email' => sanitizeInput($_POST['business_email']),
                    'operating_hours' => sanitizeInput($_POST['operating_hours'] ?? ''),
                    'delivery_radius' => (int)($_POST['delivery_radius'] ?? 5),
                    'min_order_amount' => (float)($_POST['min_order_amount'] ?? 0),
                    'delivery_fee' => (float)($_POST['delivery_fee'] ?? 0),
                    'tax_rate' => (float)($_POST['tax_rate'] ?? 0),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                // Check if settings exist
                try {
                    $existing = fetchRow("SELECT id FROM vendor_settings WHERE vendor_id = ?", [$actualVendorId]);
                    
                    if ($existing) {
                        updateData('vendor_settings', $settingsData, "vendor_id = ?", [$actualVendorId]);
                    } else {
                        $settingsData['created_at'] = date('Y-m-d H:i:s');
                        insertData('vendor_settings', $settingsData);
                    }
                } catch (Exception $e) {
                    // If there's still an issue, just create the record
                    $settingsData['created_at'] = date('Y-m-d H:i:s');
                    insertData('vendor_settings', $settingsData);
                }
                
                $success = 'Business settings updated successfully!';
            } catch (Exception $e) {
                $error = 'Failed to update business settings: ' . $e->getMessage();
            }
            break;
            
        case 'upload_profile_image':
            try {
                // Debug file upload
                if (!isset($_FILES['profile_image'])) {
                    throw new Exception('No file was uploaded. Please select an image file.');
                }
                
                $file = $_FILES['profile_image'];
                
                // Check for upload errors
                switch ($file['error']) {
                    case UPLOAD_ERR_OK:
                        break;
                    case UPLOAD_ERR_NO_FILE:
                        throw new Exception('No file was selected. Please choose an image file.');
                    case UPLOAD_ERR_INI_SIZE:
                    case UPLOAD_ERR_FORM_SIZE:
                        throw new Exception('File is too large. Maximum size allowed is 5MB.');
                    case UPLOAD_ERR_PARTIAL:
                        throw new Exception('File upload was interrupted. Please try again.');
                    case UPLOAD_ERR_NO_TMP_DIR:
                        throw new Exception('Server configuration error: No temporary directory.');
                    case UPLOAD_ERR_CANT_WRITE:
                        throw new Exception('Server configuration error: Cannot write file.');
                    case UPLOAD_ERR_EXTENSION:
                        throw new Exception('File upload blocked by server extension.');
                    default:
                        throw new Exception('Unknown file upload error occurred.');
                }
                
                // Additional validation
                if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
                    throw new Exception('Invalid file upload. Please try again.');
                }
                
                $imagePath = handleImageUpload($file, 'profile', $vendorId);
                
                // Update user avatar (personal profile picture)
                updateData('users', ['avatar' => $imagePath], "id = ?", [$vendorId]);
                
                $success = 'Profile picture updated successfully!';
            } catch (Exception $e) {
                $error = 'Failed to upload profile picture: ' . $e->getMessage();
            }
            break;
            
        case 'upload_cover_image':
            try {
                // Debug file upload
                if (!isset($_FILES['cover_image'])) {
                    throw new Exception('No file was uploaded. Please select an image file.');
                }
                
                $file = $_FILES['cover_image'];
                
                // Check for upload errors
                switch ($file['error']) {
                    case UPLOAD_ERR_OK:
                        break;
                    case UPLOAD_ERR_NO_FILE:
                        throw new Exception('No file was selected. Please choose an image file.');
                    case UPLOAD_ERR_INI_SIZE:
                    case UPLOAD_ERR_FORM_SIZE:
                        throw new Exception('File is too large. Maximum size allowed is 5MB.');
                    case UPLOAD_ERR_PARTIAL:
                        throw new Exception('File upload was interrupted. Please try again.');
                    case UPLOAD_ERR_NO_TMP_DIR:
                        throw new Exception('Server configuration error: No temporary directory.');
                    case UPLOAD_ERR_CANT_WRITE:
                        throw new Exception('Server configuration error: Cannot write file.');
                    case UPLOAD_ERR_EXTENSION:
                        throw new Exception('File upload blocked by server extension.');
                    default:
                        throw new Exception('Unknown file upload error occurred.');
                }
                
                // Additional validation
                if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
                    throw new Exception('Invalid file upload. Please try again.');
                }
                
                $imagePath = handleImageUpload($file, 'cover', $vendorId);
                
                // Update or create vendor record - handle missing table gracefully
                try {
                    $vendorRecord = fetchRow("SELECT id FROM vendors WHERE owner_id = ?", [$vendorId]);
                    
                    if ($vendorRecord) {
                        updateData('vendors', ['banner_image' => $imagePath], "owner_id = ?", [$vendorId]);
                    } else {
                        // Create basic vendor record if it doesn't exist
                        // First ensure we have required reference data
                        $businessTypeId = fetchValue("SELECT id FROM business_types LIMIT 1") ?? 1;
                        $cityId = fetchValue("SELECT id FROM cities LIMIT 1") ?? 1;
                        
                        // Generate unique slug
                        $vendorName = $vendor['name'] ?? 'My Business';
                        $baseSlug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $vendorName), '-'));
                        
                        // If slug is empty after sanitization, use 'vendor' as base
                        if (empty($baseSlug)) {
                            $baseSlug = 'vendor';
                        }
                        
                        // Create unique slug with user ID and timestamp to avoid duplicates
                        $uniqueSlug = $baseSlug . '-' . $vendorId . '-' . time();
                        
                        $vendorData = [
                            'owner_id' => $vendorId,
                            'business_type_id' => $businessTypeId,
                            'name' => $vendorName,
                            'slug' => $uniqueSlug,
                            'address' => 'Not specified',
                            'city_id' => $cityId,
                            'phone' => $vendor['phone'] ?? '',
                            'email' => $vendor['email'] ?? '',
                            'banner_image' => $imagePath
                        ];
                        insertData('vendors', $vendorData);
                    }
                } catch (Exception $e) {
                    // If vendors table operations fail, log the error but still report success
                    error_log("Vendor table operation failed for cover image: " . $e->getMessage());
                }
                
                $success = 'Cover picture updated successfully!';
            } catch (Exception $e) {
                $error = 'Failed to upload cover picture: ' . $e->getMessage();
            }
            break;
    }
}

// Get vendor data
try {
    $vendor = fetchRow("SELECT * FROM users WHERE id = ?", [$vendorId]);
    
    // Get vendor profile data from vendors table
    $vendorProfile = null;
    $actualVendorId = null;
    try {
        $vendorProfile = fetchRow("SELECT id, logo, banner_image, name, description, address, phone, email FROM vendors WHERE owner_id = ?", [$vendorId]);
        if ($vendorProfile) {
            $actualVendorId = $vendorProfile['id'];
        }
    } catch (Exception $e) {
        error_log("Vendor profile query error: " . $e->getMessage());
        $vendorProfile = null;
    }
    
    // Get business settings - handle missing table gracefully
    $businessSettings = null;
    try {
        // First, ensure the table exists
        $pdo->exec("CREATE TABLE IF NOT EXISTS vendor_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            vendor_id INT NOT NULL,
            business_name VARCHAR(255),
            business_address TEXT,
            business_phone VARCHAR(20),
            business_email VARCHAR(255),
            operating_hours TEXT,
            delivery_radius INT DEFAULT 5,
            min_order_amount DECIMAL(10,2) DEFAULT 0,
            delivery_fee DECIMAL(10,2) DEFAULT 0,
            tax_rate DECIMAL(5,2) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_vendor (vendor_id)
        )");
        
        // Try to get settings from vendor_settings table
        if ($actualVendorId) {
            $businessSettings = fetchRow("SELECT * FROM vendor_settings WHERE vendor_id = ?", [$actualVendorId]);
        }
    } catch (Exception $e) {
        error_log("Business settings query error: " . $e->getMessage());
        $businessSettings = null;
    }
    
    // Set defaults - prioritize vendors table data
    if (!$businessSettings) {
        $businessSettings = [
            'business_name' => $vendorProfile['name'] ?? $vendor['name'] ?? '',
            'business_address' => $vendorProfile['address'] ?? '',
            'business_phone' => $vendorProfile['phone'] ?? '',
            'business_email' => $vendorProfile['email'] ?? '',
            'operating_hours' => '',
            'delivery_radius' => 5,
            'min_order_amount' => 0,
            'delivery_fee' => 0,
            'tax_rate' => 0
        ];
    } else {
        // Merge with vendors table data (vendors table takes priority for name, address, phone, email)
        $businessSettings['business_name'] = $vendorProfile['name'] ?? $businessSettings['business_name'] ?? '';
        $businessSettings['business_address'] = $vendorProfile['address'] ?? $businessSettings['business_address'] ?? '';
        $businessSettings['business_phone'] = $vendorProfile['phone'] ?? $businessSettings['business_phone'] ?? '';
        $businessSettings['business_email'] = $vendorProfile['email'] ?? $businessSettings['business_email'] ?? '';
    }
    
} catch (Exception $e) {
    $vendor = [];
    $vendorProfile = null;
    $businessSettings = [
        'business_name' => '',
        'business_address' => '',
        'business_phone' => '',
        'business_email' => '',
        'operating_hours' => '',
        'delivery_radius' => 5,
        'min_order_amount' => 0,
        'delivery_fee' => 0,
        'tax_rate' => 0
    ];
    $error = 'Failed to load settings. Please ensure database tables are properly set up.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - ORDIVO Vendor</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../assets/css/ordivo-responsive.css" rel="stylesheet">
    
    <style>
        :root {
            --ordivo-primary: #10b981;
            --ordivo-secondary: #059669;
            --ordivo-accent: #f97316;
            --ordivo-light: #f0fdf4;
            --ordivo-dark: #1a1a1a;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
        }

        /* Mobile First - Sidebar hidden by default on mobile */
        .sidebar {
            background: linear-gradient(180deg, #10b981 0%, #059669 100%);
            min-height: 100vh;
            width: 250px;
            position: fixed;
            left: -250px; /* Hidden by default on mobile */
            top: 0;
            z-index: 1000;
            transition: left 0.3s ease;
            overflow-y: auto;
        }

        .sidebar.show {
            left: 0;
        }

        /* Overlay for mobile */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 999;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .sidebar-overlay.show {
            display: block;
            opacity: 1;
        }

        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.9);
            padding: 12px 20px;
            border-radius: 8px;
            margin: 2px 10px;
            transition: all 0.3s ease;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: #ffffff;
            color: #10b981;
            border-radius: 0.5rem;
            transform: translateX(5px);
        }

        .sidebar .nav-link.active i {
            color: #10b981;
        }
            transform: translateX(5px);
        }

        .main-content {
            margin-left: 0; /* No margin on mobile */
            padding: 0.625rem 1rem 1rem; /* 10px top, 1rem sides and bottom */
            transition: all 0.3s ease;
        }

        .header-card {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border-radius: 15px;
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border-top: 4px solid #10b981;
        }

        .header-card-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        /* Inline hamburger button for mobile - positioned in header card */
        .sidebar-toggle-inline {
            display: block;
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.3);
            border: 2px solid white;
            border-radius: 8px;
            color: white;
            font-size: 1.1rem;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            transition: all 0.3s ease;
            flex-shrink: 0;
        }

        .sidebar-toggle-inline:hover {
            background: rgba(255, 255, 255, 0.5);
            transform: scale(1.05);
        }

        .header-info {
            flex: 1;
            min-width: 0;
        }

        .header-info h1 {
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
        }

        .header-info p {
            font-size: 0.875rem;
            margin-bottom: 0;
        }

        @media (max-width: 576px) {
            .header-card-content {
                flex-direction: column;
                align-items: stretch;
            }

            .sidebar-toggle-inline {
                width: 100%;
            }

            .header-info {
                width: 100%;
                text-align: center;
            }
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            margin-bottom: 2rem;
        }

        .card:hover {
            box-shadow: 0 15px 35px #e5e7eb;
        }

        .btn-primary {
            background: #10b981; 100%);
            border: none;
            border-radius: 10px;
            padding: 10px 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 8px #e5e7eb;
        }

        .settings-nav {
            border-right: 1px solid #eee;
        }

        .settings-nav .nav-link {
            color: #6c757d;
            padding: 1rem 1.5rem;
            border-radius: 0;
            border-bottom: 1px solid #eee;
        }

        .settings-nav .nav-link.active {
            background: var(--ordivo-light);
            color: var(--ordivo-primary);
            border-right: 3px solid var(--ordivo-primary);
        }

        /* Settings navigation in header */
        .settings-nav-header {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            padding: 0.5rem;
        }

        .settings-nav-header .nav-link {
            color: white;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
            border: none;
        }

        .settings-nav-header .nav-link:hover,
        .settings-nav-header .nav-link.active {
            background: rgba(255, 255, 255, 0.3);
            color: white;
        }

        @media (min-width: 992px) {
            .settings-nav-header {
                display: flex;
                gap: 0.5rem;
            }
        }

        .profile-image-container, .cover-image-container {
            position: relative;
            overflow: hidden;
            border-radius: 10px;
            border: 2px dashed #dee2e6;
            transition: all 0.3s ease;
        }

        .profile-image-container:hover, .cover-image-container:hover {
            border-color: var(--ordivo-primary);
        }

        .profile-preview {
            width: 200px;
            height: 200px;
            object-fit: cover;
            border-radius: 10px;
            border: 3px solid var(--ordivo-primary);
        }

        .cover-preview {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 10px;
            border: 3px solid var(--ordivo-primary);
        }

        .profile-placeholder, .cover-placeholder {
            width: 200px;
            height: 200px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
            border-radius: 10px;
            margin: 0 auto;
        }

        .cover-placeholder {
            width: 100%;
            height: 150px;
        }

        .image-upload-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(255, 107, 53, 0.9);
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .image-upload-btn:hover {
            background: var(--ordivo-primary);
            transform: scale(1.1);
        }

        /* Tablet and up */
        @media (min-width: 768px) {
            .sidebar-toggle-inline {
                display: none; /* Hide inline hamburger on tablet+ */
            }

            .sidebar {
                left: 0; /* Always visible on tablet+ */
            }

            .sidebar-overlay {
                display: none !important;
            }

            .main-content {
                margin-left: 250px;
                padding: 1.5rem;
            }

            .header-card {
                padding: 2rem;
                margin-bottom: 2rem;
            }

            .header-info h1 {
                font-size: 1.8rem;
            }

            .header-info p {
                font-size: 1rem;
            }
        }

        /* Desktop */
        @media (min-width: 1200px) {
            .main-content {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Sidebar -->
    <nav class="sidebar" id="sidebar">
        <div class="p-4">
            <div class="d-flex align-items-center mb-4">
                <?php if (!empty($vendorLogo)): ?>
                    <img src="<?= htmlspecialchars($vendorLogo) ?>" alt="<?= htmlspecialchars($vendorBusinessName) ?>" 
                         style="height: 60px; width: 60px; object-fit: cover; border-radius: 10px; margin-right: 12px; background: white; padding: 5px;"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div style="display: none; width: 60px; height: 60px; background: #ffffff; border-radius: 10px; align-items: center; justify-content: center; margin-right: 12px;">
                        <i class="fas fa-store fa-2x text-white"></i>
                    </div>
                <?php else: ?>
                    <div style="display: flex; width: 60px; height: 60px; background: #ffffff; border-radius: 10px; align-items: center; justify-content: center; margin-right: 12px;">
                        <i class="fas fa-store fa-2x text-white"></i>
                    </div>
                <?php endif; ?>
                <div>
                    <h5 class="text-white mb-0" style="font-size: 1.1rem; font-weight: 600;"><?= htmlspecialchars($vendorBusinessName) ?></h5>
                    <small class="text-white-50">Vendor Portal</small>
                </div>
            </div>
        </div>
        
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="dashboard.php">
                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="products.php">
                    <i class="fas fa-box me-2"></i>Products
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="orders.php">
                    <i class="fas fa-shopping-cart me-2"></i>Orders
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="inventory.php">
                    <i class="fas fa-boxes me-2"></i>Inventory
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="categories.php">
                    <i class="fas fa-tags me-2"></i>Categories
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="../kitchen/dashboard.php">
                    <i class="fas fa-utensils me-2"></i>Kitchen
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="staff.php">
                    <i class="fas fa-users me-2"></i>Staff
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="settings.php">
                    <i class="fas fa-cog me-2"></i>Settings
                </a>
            </li>
            <li class="nav-item mt-4">
                <a class="nav-link" href="../auth/logout.php">
                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                </a>
            </li>
        </ul>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="header-card">
            <div class="header-card-content">
                <!-- Mobile Hamburger Button -->
                <button class="sidebar-toggle-inline" id="sidebarToggleInline">
                    <i class="fas fa-bars"></i>
                </button>

                <div class="header-info">
                    <h1>
                        <i class="fas fa-cog me-2"></i>Settings
                    </h1>
                    <p class="opacity-75">Manage your account and business settings</p>
                </div>
            </div>
            
            <!-- Settings Navigation Dropdown -->
            <div class="mt-3">
                <button class="btn btn-light w-100 d-lg-none" type="button" data-bs-toggle="collapse" data-bs-target="#settingsMenu" aria-expanded="false" aria-controls="settingsMenu">
                    <i class="fas fa-bars me-2"></i>Settings Menu
                </button>
                <div class="collapse d-lg-block" id="settingsMenu">
                    <nav class="nav flex-column flex-lg-row settings-nav-header mt-3 mt-lg-0">
                        <a class="nav-link active" href="#profile" data-bs-toggle="tab" onclick="closeMenuOnMobile()">
                            <i class="fas fa-user me-2"></i>Profile
                        </a>
                        <a class="nav-link" href="#images" data-bs-toggle="tab" onclick="closeMenuOnMobile()">
                            <i class="fas fa-images me-2"></i>Shop Images
                        </a>
                        <a class="nav-link" href="#security" data-bs-toggle="tab" onclick="closeMenuOnMobile()">
                            <i class="fas fa-lock me-2"></i>Security
                        </a>
                        <a class="nav-link" href="#business" data-bs-toggle="tab" onclick="closeMenuOnMobile()">
                            <i class="fas fa-building me-2"></i>Business
                        </a>
                    </nav>
                </div>
            </div>
        </div>

        <!-- Alerts -->
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php if (strpos($success, 'Business settings updated') !== false): ?>
                <script>
                    // Auto-refresh after 1.5 seconds to show updated business name in sidebar
                    setTimeout(function() {
                        window.location.href = window.location.href.split('?')[0] + '?updated=' + Date.now();
                    }, 1500);
                </script>
            <?php endif; ?>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                <?php if (strpos($error, 'database') !== false || strpos($error, 'table') !== false): ?>
                    <hr>
                    <p class="mb-2"><strong>Database Setup Required:</strong></p>
                    <p class="mb-2">It looks like some database tables are missing. Please run the database setup to create the necessary tables.</p>
                    <a href="../setup_vendor_tables.php" class="btn btn-sm btn-outline-primary" target="_blank">
                        <i class="fas fa-database me-2"></i>Run Database Setup
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Settings Content -->
        <div class="tab-content">
            <!-- Profile Settings -->
            <div class="tab-pane fade show active" id="profile">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-user me-2"></i>Profile Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="update_profile">
                            
                            <div class="mb-3">
                                <label for="name" class="form-label">Full Name *</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($vendor['name'] ?? '') ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address *</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($vendor['email'] ?? '') ?>" required>
                            </div>
                                    
                                    <div class="mb-3">
                                        <label for="phone" class="form-label">Phone Number</label>
                                        <input type="tel" class="form-control" id="phone" name="phone" value="<?= htmlspecialchars($vendor['phone'] ?? '') ?>">
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Update Profile
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Shop Images Settings -->
                    <div class="tab-pane fade" id="images">
                        <div class="row">
                            <!-- Profile Picture -->
                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">
                                            <i class="fas fa-user-circle me-2"></i>Profile Picture
                                        </h5>
                                    </div>
                                    <div class="card-body text-center">
                                        <div class="profile-image-container mb-3">
                                            <?php if (!empty($vendorProfile['logo'])): ?>
                                                <img src="../<?= htmlspecialchars($vendorProfile['logo']) ?>" 
                                                     alt="Profile Picture" 
                                                     class="profile-preview"
                                                     id="profilePreview">
                                            <?php else: ?>
                                                <div class="profile-placeholder" id="profilePlaceholder">
                                                    <i class="fas fa-store fa-3x text-muted"></i>
                                                    <p class="text-muted mt-2">No profile picture</p>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <form method="POST" enctype="multipart/form-data" id="profileForm">
                                            <input type="hidden" name="action" value="upload_profile_image">
                                            <div class="mb-3">
                                                <input type="file" class="form-control" id="profileImageInput" 
                                                       name="profile_image" accept="image/*" onchange="previewImage(this, 'profilePreview', 'profilePlaceholder')">
                                                <small class="text-muted">Max 5MB. JPEG, PNG, GIF, WebP allowed.</small>
                                            </div>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-upload me-2"></i>Upload Profile Picture
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Cover Picture -->
                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">
                                            <i class="fas fa-image me-2"></i>Cover Picture
                                        </h5>
                                    </div>
                                    <div class="card-body text-center">
                                        <div class="cover-image-container mb-3">
                                            <?php if (!empty($vendorProfile['banner_image'])): ?>
                                                <img src="../<?= htmlspecialchars($vendorProfile['banner_image']) ?>" 
                                                     alt="Cover Picture" 
                                                     class="cover-preview"
                                                     id="coverPreview">
                                            <?php else: ?>
                                                <div class="cover-placeholder" id="coverPlaceholder">
                                                    <i class="fas fa-image fa-3x text-muted"></i>
                                                    <p class="text-muted mt-2">No cover picture</p>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <form method="POST" enctype="multipart/form-data" id="coverForm">
                                            <input type="hidden" name="action" value="upload_cover_image">
                                            <div class="mb-3">
                                                <input type="file" class="form-control" id="coverImageInput" 
                                                       name="cover_image" accept="image/*" onchange="previewImage(this, 'coverPreview', 'coverPlaceholder')">
                                                <small class="text-muted">Max 5MB. JPEG, PNG, GIF, WebP allowed.</small>
                                            </div>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-upload me-2"></i>Upload Cover Picture
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Image Guidelines -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-info-circle me-2"></i>Image Guidelines & System Status
                                </h5>
                            </div>
                            <div class="card-body">
                                <!-- System Status -->
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <h6><i class="fas fa-server me-2 text-primary"></i>System Status</h6>
                                        <div class="d-flex align-items-center mb-2">
                                            <?php if (extension_loaded('gd')): ?>
                                                <i class="fas fa-check-circle text-success me-2"></i>
                                                <span class="text-success">GD Extension: Enabled (Thumbnail generation available)</span>
                                            <?php else: ?>
                                                <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                                                <span class="text-warning">GD Extension: Disabled (Thumbnails will not be generated)</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <?php if (is_writable('../uploads/vendors/')): ?>
                                                <i class="fas fa-check-circle text-success me-2"></i>
                                                <span class="text-success">Upload Directory: Writable</span>
                                            <?php else: ?>
                                                <i class="fas fa-times-circle text-danger me-2"></i>
                                                <span class="text-danger">Upload Directory: Not writable</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="d-flex align-items-center mt-2">
                                            <?php 
                                            $maxFileSize = ini_get('upload_max_filesize');
                                            $maxPostSize = ini_get('post_max_size');
                                            ?>
                                            <i class="fas fa-info-circle text-info me-2"></i>
                                            <span class="text-info">Max Upload Size: <?= $maxFileSize ?> (Post: <?= $maxPostSize ?>)</span>
                                        </div>
                                        <?php if (!extension_loaded('gd')): ?>
                                            <div class="alert alert-info mt-3">
                                                <i class="fas fa-info-circle me-2"></i>
                                                <strong>Note:</strong> To enable thumbnail generation, please enable the GD extension in your PHP configuration.
                                                Images will still upload successfully without thumbnails.
                                                <br>
                                                <a href="../enable_gd_extension.md" target="_blank" class="btn btn-sm btn-outline-info mt-2">
                                                    <i class="fas fa-external-link-alt me-1"></i>View Setup Guide
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6><i class="fas fa-user-circle me-2 text-primary"></i>Profile Picture</h6>
                                        <ul class="list-unstyled text-muted">
                                            <li><i class="fas fa-check text-success me-2"></i>Square format (1:1 ratio) recommended</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Minimum 200x200 pixels</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Shows your business logo or identity</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Clear, high-quality image</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <h6><i class="fas fa-image me-2 text-primary"></i>Cover Picture</h6>
                                        <ul class="list-unstyled text-muted">
                                            <li><i class="fas fa-check text-success me-2"></i>Wide format (16:9 ratio) recommended</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Minimum 800x450 pixels</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Showcases your products or restaurant</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Attractive and professional</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Security Settings -->
                    <div class="tab-pane fade" id="security">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-lock me-2"></i>Change Password
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="change_password">
                                    
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label">Current Password *</label>
                                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">New Password *</label>
                                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                                        <small class="text-muted">Minimum 6 characters</small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">Confirm New Password *</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-key me-2"></i>Change Password
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Business Settings -->
                    <div class="tab-pane fade" id="business">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-building me-2"></i>Business Information
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="action" value="update_business">
                                    
                                    <!-- Business Logo Upload Section -->
                                    <div class="mb-4">
                                        <label class="form-label fw-bold">Business Logo</label>
                                        <div class="row align-items-center">
                                            <div class="col-md-3 text-center mb-3 mb-md-0">
                                                <?php if (!empty($vendorProfile['logo'])): ?>
                                                    <img src="../<?= htmlspecialchars($vendorProfile['logo']) ?>" 
                                                         alt="Business Logo" 
                                                         class="img-thumbnail"
                                                         style="width: 150px; height: 150px; object-fit: cover; border-radius: 10px;"
                                                         id="current_business_logo">
                                                <?php else: ?>
                                                    <div class="bg-light d-flex align-items-center justify-content-center" 
                                                         style="width: 150px; height: 150px; border-radius: 10px; margin: 0 auto;"
                                                         id="logo_placeholder">
                                                        <i class="fas fa-store fa-3x text-muted"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-md-9">
                                                <div class="mb-2">
                                                    <input type="file" 
                                                           class="form-control" 
                                                           id="business_logo" 
                                                           name="business_logo" 
                                                           accept="image/*"
                                                           onchange="previewBusinessLogo(this)">
                                                    <small class="text-muted">
                                                        <i class="fas fa-info-circle me-1"></i>
                                                        Upload your business logo (Max 5MB, JPG/PNG/GIF). This will appear in the sidebar.
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <hr class="my-4">
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="business_name" class="form-label">Business Name</label>
                                            <input type="text" class="form-control" id="business_name" name="business_name" value="<?= htmlspecialchars($businessSettings['business_name'] ?? '') ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="business_phone" class="form-label">Business Phone</label>
                                            <input type="tel" class="form-control" id="business_phone" name="business_phone" value="<?= htmlspecialchars($businessSettings['business_phone'] ?? '') ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="business_email" class="form-label">Business Email</label>
                                        <input type="email" class="form-control" id="business_email" name="business_email" value="<?= htmlspecialchars($businessSettings['business_email'] ?? '') ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="business_address" class="form-label">Business Address</label>
                                        <textarea class="form-control" id="business_address" name="business_address" rows="3"><?= htmlspecialchars($businessSettings['business_address'] ?? '') ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="operating_hours" class="form-label">Operating Hours</label>
                                        <input type="text" class="form-control" id="operating_hours" name="operating_hours" value="<?= htmlspecialchars($businessSettings['operating_hours'] ?? '') ?>" placeholder="e.g., Mon-Fri: 9AM-9PM, Sat-Sun: 10AM-8PM">
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="delivery_radius" class="form-label">Delivery Radius (km)</label>
                                            <input type="number" class="form-control" id="delivery_radius" name="delivery_radius" value="<?= $businessSettings['delivery_radius'] ?? 5 ?>" min="1" max="50">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="min_order_amount" class="form-label">Minimum Order Amount (৳)</label>
                                            <input type="number" class="form-control" id="min_order_amount" name="min_order_amount" value="<?= $businessSettings['min_order_amount'] ?? 0 ?>" step="0.01" min="0">
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="delivery_fee" class="form-label">Delivery Fee (৳)</label>
                                            <input type="number" class="form-control" id="delivery_fee" name="delivery_fee" value="<?= $businessSettings['delivery_fee'] ?? 0 ?>" step="0.01" min="0">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="tax_rate" class="form-label">Tax Rate (%)</label>
                                            <input type="number" class="form-control" id="tax_rate" name="tax_rate" value="<?= $businessSettings['tax_rate'] ?? 0 ?>" step="0.01" min="0" max="100">
                                        </div>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Update Business Settings
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Wait for DOM to be fully loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Mobile menu toggle
            const sidebar = document.getElementById('sidebar');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            const sidebarToggleInline = document.getElementById('sidebarToggleInline');

            function toggleSidebar() {
                if (sidebar && sidebarOverlay) {
                    sidebar.classList.toggle('show');
                    sidebarOverlay.classList.toggle('show');
                }
            }

            if (sidebarToggleInline) {
                sidebarToggleInline.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    toggleSidebar();
                });
            }

            if (sidebarOverlay) {
                sidebarOverlay.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    toggleSidebar();
                });
            }
        });

        // Close settings menu on mobile after selecting an option
        function closeMenuOnMobile() {
            if (window.innerWidth < 992) {
                const settingsMenu = document.getElementById('settingsMenu');
                if (settingsMenu && settingsMenu.classList.contains('show')) {
                    const bsCollapse = new bootstrap.Collapse(settingsMenu, {
                        toggle: true
                    });
                }
            }
        }

        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (newPassword !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });

        // Image preview functionality
        function previewImage(input, previewId, placeholderId) {
            const file = input.files[0];
            const preview = document.getElementById(previewId);
            const placeholder = document.getElementById(placeholderId);
            
            if (file) {
                // Validate file size (5MB = 5 * 1024 * 1024 bytes)
                const maxSize = 5 * 1024 * 1024;
                if (file.size > maxSize) {
                    alert('File is too large. Maximum size allowed is 5MB.');
                    input.value = '';
                    return;
                }
                
                // Validate file type
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                const fileType = file.type.toLowerCase();
                if (!allowedTypes.includes(fileType)) {
                    alert('Invalid file type. Only JPEG, PNG, GIF, and WebP images are allowed.');
                    input.value = '';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    if (preview) {
                        preview.src = e.target.result;
                        preview.style.display = 'block';
                    } else {
                        // Create new preview image
                        const newPreview = document.createElement('img');
                        newPreview.id = previewId;
                        newPreview.src = e.target.result;
                        newPreview.className = previewId === 'profilePreview' ? 'profile-preview' : 'cover-preview';
                        
                        if (placeholder) {
                            placeholder.parentNode.replaceChild(newPreview, placeholder);
                        }
                    }
                    
                    if (placeholder) {
                        placeholder.style.display = 'none';
                    }
                };
                reader.readAsDataURL(file);
            }
        }

        // Business logo preview functionality
        function previewBusinessLogo(input) {
            const file = input.files[0];
            
            if (file) {
                // Validate file size (5MB)
                const maxSize = 5 * 1024 * 1024;
                if (file.size > maxSize) {
                    alert('File is too large. Maximum size allowed is 5MB.');
                    input.value = '';
                    return;
                }
                
                // Validate file type
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                const fileType = file.type.toLowerCase();
                if (!allowedTypes.includes(fileType)) {
                    alert('Invalid file type. Only JPEG, PNG, GIF, and WebP images are allowed.');
                    input.value = '';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    const currentLogo = document.getElementById('current_business_logo');
                    const placeholder = document.getElementById('logo_placeholder');
                    
                    if (currentLogo) {
                        currentLogo.src = e.target.result;
                    } else if (placeholder) {
                        const img = document.createElement('img');
                        img.id = 'current_business_logo';
                        img.src = e.target.result;
                        img.className = 'img-thumbnail';
                        img.style.cssText = 'width: 150px; height: 150px; object-fit: cover; border-radius: 10px;';
                        placeholder.parentNode.replaceChild(img, placeholder);
                    }
                };
                reader.readAsDataURL(file);
            }
        }

        // Form submission with loading states and validation
        document.getElementById('profileForm').addEventListener('submit', function(e) {
            const fileInput = document.getElementById('profileImageInput');
            const btn = this.querySelector('button[type="submit"]');
            
            if (!fileInput.files || fileInput.files.length === 0) {
                e.preventDefault();
                alert('Please select an image file before uploading.');
                return;
            }
            
            const file = fileInput.files[0];
            console.log('Profile upload - File details:', {
                name: file.name,
                size: file.size,
                type: file.type,
                lastModified: file.lastModified
            });
            
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Uploading...';
            btn.disabled = true;
        });

        document.getElementById('coverForm').addEventListener('submit', function(e) {
            const fileInput = document.getElementById('coverImageInput');
            const btn = this.querySelector('button[type="submit"]');
            
            if (!fileInput.files || fileInput.files.length === 0) {
                e.preventDefault();
                alert('Please select an image file before uploading.');
                return;
            }
            
            const file = fileInput.files[0];
            console.log('Cover upload - File details:', {
                name: file.name,
                size: file.size,
                type: file.type,
                lastModified: file.lastModified
            });
            
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Uploading...';
            btn.disabled = true;
        });
    </script>
</body>
</html>