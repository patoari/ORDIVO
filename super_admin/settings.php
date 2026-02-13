<?php
/**
 * ORDIVO - System Settings
 * Complete platform configuration and settings management with image upload
 */

require_once '../config/db_connection.php';

// Check if user is logged in and is super admin first
if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'super_admin') {
    header('Location: ../auth/login.php');
    exit;
}

// Now safely require image_manager.php
require_once 'image_manager.php';

// Handle image uploads
function handleSettingsImageUpload($file, $imageType) {
    try {
        $uploadDir = '../uploads/settings/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
        $maxSize = 10 * 1024 * 1024; // 10MB for settings images
        
        if (!in_array($file['type'], $allowedTypes)) {
            throw new Exception('Invalid file type. Only JPEG, PNG, GIF, WebP, and SVG files are allowed.');
        }
        
        if ($file['size'] > $maxSize) {
            throw new Exception('File size too large. Maximum size is 10MB.');
        }
        
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = $imageType . '_' . time() . '_' . uniqid() . '.' . $extension;
        $filepath = $uploadDir . $filename;
        
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            throw new Exception('Failed to upload file.');
        }
        
        return 'uploads/settings/' . $filename;
    } catch (Exception $e) {
        throw new Exception('Image upload failed: ' . $e->getMessage());
    }
}

// Handle settings updates
$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'update_general':
            $siteName = sanitizeInput($_POST['site_name'] ?? '');
            $siteTagline = sanitizeInput($_POST['site_tagline'] ?? '');
            $logoUrl = sanitizeInput($_POST['logo_url'] ?? '');
            
            // Handle logo upload
            if (isset($_FILES['logo_upload']) && $_FILES['logo_upload']['error'] === UPLOAD_ERR_OK) {
                try {
                    $logoUrl = handleSettingsImageUpload($_FILES['logo_upload'], 'logo');
                } catch (Exception $e) {
                    $error = $e->getMessage();
                    break;
                }
            }
            
            // Handle favicon upload
            $faviconUrl = sanitizeInput($_POST['favicon_url'] ?? '');
            if (isset($_FILES['favicon_upload']) && $_FILES['favicon_upload']['error'] === UPLOAD_ERR_OK) {
                try {
                    $faviconUrl = handleSettingsImageUpload($_FILES['favicon_upload'], 'favicon');
                } catch (Exception $e) {
                    $error = $e->getMessage();
                    break;
                }
            }
            
            try {
                $settingsExist = fetchValue("SELECT COUNT(*) FROM site_settings");
                
                $updateData = [
                    'site_name' => $siteName,
                    'site_tagline' => $siteTagline,
                    'logo_url' => $logoUrl,
                    'favicon_url' => $faviconUrl,
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                if ($settingsExist) {
                    updateData('site_settings', $updateData, 'id = 1');
                } else {
                    $updateData['created_at'] = date('Y-m-d H:i:s');
                    insertData('site_settings', $updateData);
                }
                $success = 'General settings updated successfully!';
            } catch (Exception $e) {
                $error = 'Failed to update general settings: ' . $e->getMessage();
            }
            break;
            
        case 'update_hero':
            $heroTitle = sanitizeInput($_POST['hero_title'] ?? '');
            $heroSubtitle = sanitizeInput($_POST['hero_subtitle'] ?? '');
            $heroButtonText = sanitizeInput($_POST['hero_button_text'] ?? '');
            $heroButtonLink = sanitizeInput($_POST['hero_button_link'] ?? '');
            $heroBackgroundImage = sanitizeInput($_POST['hero_background_image'] ?? '');
            
            // Handle hero background upload
            if (isset($_FILES['hero_bg_upload']) && $_FILES['hero_bg_upload']['error'] === UPLOAD_ERR_OK) {
                try {
                    $heroBackgroundImage = handleSettingsImageUpload($_FILES['hero_bg_upload'], 'hero_background');
                } catch (Exception $e) {
                    $error = $e->getMessage();
                    break;
                }
            }
            
            try {
                $settingsExist = fetchValue("SELECT COUNT(*) FROM site_settings");
                
                $updateData = [
                    'hero_title' => $heroTitle,
                    'hero_subtitle' => $heroSubtitle,
                    'hero_button_text' => $heroButtonText,
                    'hero_button_link' => $heroButtonLink,
                    'hero_background_image' => $heroBackgroundImage,
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                if ($settingsExist) {
                    updateData('site_settings', $updateData, 'id = 1');
                } else {
                    $updateData['created_at'] = date('Y-m-d H:i:s');
                    insertData('site_settings', $updateData);
                }
                $success = 'Hero section updated successfully!';
            } catch (Exception $e) {
                $error = 'Failed to update hero section: ' . $e->getMessage();
            }
            break;
            
        case 'update_contact':
            $contactEmail = sanitizeInput($_POST['contact_email'] ?? '');
            $contactPhone = sanitizeInput($_POST['contact_phone'] ?? '');
            $businessAddress = sanitizeInput($_POST['business_address'] ?? '');
            $supportEmail = sanitizeInput($_POST['support_email'] ?? '');
            $salesPhone = sanitizeInput($_POST['sales_phone'] ?? '');
            $whatsappNumber = sanitizeInput($_POST['whatsapp_number'] ?? '');
            $facebookUrl = sanitizeInput($_POST['facebook_url'] ?? '');
            $instagramUrl = sanitizeInput($_POST['instagram_url'] ?? '');
            $twitterUrl = sanitizeInput($_POST['twitter_url'] ?? '');
            
            try {
                // Store contact info in a JSON field or create separate contact_settings table
                // For now, we'll use a simple approach with a separate table
                $contactData = [
                    'contact_email' => $contactEmail,
                    'contact_phone' => $contactPhone,
                    'business_address' => $businessAddress,
                    'support_email' => $supportEmail,
                    'sales_phone' => $salesPhone,
                    'whatsapp_number' => $whatsappNumber,
                    'facebook_url' => $facebookUrl,
                    'instagram_url' => $instagramUrl,
                    'twitter_url' => $twitterUrl,
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                // Check if contact settings exist
                $contactExists = fetchValue("SELECT COUNT(*) FROM contact_settings");
                
                if ($contactExists) {
                    updateData('contact_settings', $contactData, 'id = 1');
                } else {
                    $contactData['created_at'] = date('Y-m-d H:i:s');
                    insertData('contact_settings', $contactData);
                }
                
                $success = 'Contact information updated successfully!';
            } catch (Exception $e) {
                // If contact_settings table doesn't exist, create it
                try {
                    $pdo = getDbConnection();
                    $pdo->exec("CREATE TABLE IF NOT EXISTS `contact_settings` (
                        `id` int(11) NOT NULL AUTO_INCREMENT,
                        `contact_email` varchar(100) DEFAULT NULL,
                        `contact_phone` varchar(20) DEFAULT NULL,
                        `business_address` text DEFAULT NULL,
                        `support_email` varchar(100) DEFAULT NULL,
                        `sales_phone` varchar(20) DEFAULT NULL,
                        `whatsapp_number` varchar(20) DEFAULT NULL,
                        `facebook_url` varchar(255) DEFAULT NULL,
                        `instagram_url` varchar(255) DEFAULT NULL,
                        `twitter_url` varchar(255) DEFAULT NULL,
                        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                        `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                        PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                    
                    // Now insert the data
                    $contactData['created_at'] = date('Y-m-d H:i:s');
                    insertData('contact_settings', $contactData);
                    $success = 'Contact information updated successfully!';
                } catch (Exception $e2) {
                    $error = 'Failed to update contact information: ' . $e2->getMessage();
                }
            }
            break;
            
        case 'update_layout':
            $headerLayout = sanitizeInput($_POST['header_layout'] ?? 'default');
            $footerLayout = sanitizeInput($_POST['footer_layout'] ?? 'default');
            $headerBgColor = sanitizeInput($_POST['header_bg_color'] ?? '#ffffff');
            $headerTextColor = sanitizeInput($_POST['header_text_color'] ?? '#333333');
            $footerBgColor = sanitizeInput($_POST['footer_bg_color'] ?? '#1a1a2e');
            $footerTextColor = sanitizeInput($_POST['footer_text_color'] ?? '#aaaaaa');
            $showSearchBar = isset($_POST['show_search_bar']) ? 1 : 0;
            $showSocialLinks = isset($_POST['show_social_links']) ? 1 : 0;
            $showNewsletterSignup = isset($_POST['show_newsletter_signup']) ? 1 : 0;
            
            try {
                // Check if settings exist and update layout fields
                $settingsExist = fetchValue("SELECT COUNT(*) FROM site_settings");
                
                $layoutData = [
                    'navbar_bg' => $headerBgColor,
                    'navbar_text' => $headerTextColor,
                    'footer_bg' => $footerBgColor,
                    'footer_text' => $footerTextColor,
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                if ($settingsExist) {
                    updateData('site_settings', $layoutData, 'id = 1');
                } else {
                    $layoutData['created_at'] = date('Y-m-d H:i:s');
                    insertData('site_settings', $layoutData);
                }
                
                // Store additional layout settings
                try {
                    $layoutSettings = [
                        'header_layout' => $headerLayout,
                        'footer_layout' => $footerLayout,
                        'show_search_bar' => $showSearchBar,
                        'show_social_links' => $showSocialLinks,
                        'show_newsletter_signup' => $showNewsletterSignup,
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                    
                    $layoutExists = fetchValue("SELECT COUNT(*) FROM layout_settings");
                    
                    if ($layoutExists) {
                        updateData('layout_settings', $layoutSettings, 'id = 1');
                    } else {
                        $layoutSettings['created_at'] = date('Y-m-d H:i:s');
                        insertData('layout_settings', $layoutSettings);
                    }
                } catch (Exception $e) {
                    // Create layout_settings table if it doesn't exist
                    $pdo = getDbConnection();
                    $pdo->exec("CREATE TABLE IF NOT EXISTS `layout_settings` (
                        `id` int(11) NOT NULL AUTO_INCREMENT,
                        `header_layout` varchar(50) DEFAULT 'default',
                        `footer_layout` varchar(50) DEFAULT 'default',
                        `show_search_bar` tinyint(1) DEFAULT 1,
                        `show_social_links` tinyint(1) DEFAULT 1,
                        `show_newsletter_signup` tinyint(1) DEFAULT 1,
                        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                        `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                        PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                    
                    $layoutSettings['created_at'] = date('Y-m-d H:i:s');
                    insertData('layout_settings', $layoutSettings);
                }
                
                $success = 'Layout settings updated successfully!';
            } catch (Exception $e) {
                $error = 'Failed to update layout settings: ' . $e->getMessage();
            }
            break;
            
        case 'update_business':
            $primaryColor = sanitizeInput($_POST['primary_color'] ?? '#667eea');
            $secondaryColor = sanitizeInput($_POST['secondary_color'] ?? '#764ba2');
            $accentColor = sanitizeInput($_POST['accent_color'] ?? '#ff6b6b');
            $themeMode = sanitizeInput($_POST['theme_mode'] ?? 'light');
            
            try {
                $settingsExist = fetchValue("SELECT COUNT(*) FROM site_settings");
                
                if ($settingsExist) {
                    updateData('site_settings', [
                        'primary_color' => $primaryColor,
                        'secondary_color' => $secondaryColor,
                        'accent_color' => $accentColor,
                        'theme_mode' => $themeMode,
                        'updated_at' => date('Y-m-d H:i:s')
                    ], 'id = 1');
                } else {
                    insertData('site_settings', [
                        'primary_color' => $primaryColor,
                        'secondary_color' => $secondaryColor,
                        'accent_color' => $accentColor,
                        'theme_mode' => $themeMode,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                }
                $success = 'Theme settings updated successfully!';
            } catch (Exception $e) {
                $error = 'Failed to update theme settings: ' . $e->getMessage();
            }
            break;
            
        case 'update_notifications':
            $cardStyle = sanitizeInput($_POST['card_style'] ?? 'elevated');
            $cardShadow = sanitizeInput($_POST['card_shadow'] ?? 'light');
            $cardBorderRadius = sanitizeInput($_POST['card_border_radius'] ?? '10px');
            
            try {
                $settingsExist = fetchValue("SELECT COUNT(*) FROM site_settings");
                
                if ($settingsExist) {
                    updateData('site_settings', [
                        'card_style' => $cardStyle,
                        'card_shadow' => $cardShadow,
                        'card_border_radius' => $cardBorderRadius,
                        'updated_at' => date('Y-m-d H:i:s')
                    ], 'id = 1');
                } else {
                    insertData('site_settings', [
                        'card_style' => $cardStyle,
                        'card_shadow' => $cardShadow,
                        'card_border_radius' => $cardBorderRadius,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                }
                $success = 'UI settings updated successfully!';
            } catch (Exception $e) {
                $error = 'Failed to update UI settings: ' . $e->getMessage();
            }
            break;
    }
}

// Get current settings with all new fields
try {
    $settings = fetchRow("SELECT * FROM site_settings LIMIT 1") ?: [
        'site_name' => 'ORDIVO',
        'site_tagline' => 'Fast Delivery • Fresh Products • Best Prices',
        'logo_url' => '🍔',
        'hero_background_image' => '',
        'hero_title' => 'Welcome to ORDIVO',
        'hero_subtitle' => 'Fast Delivery • Fresh Products • Best Prices',
        'hero_button_text' => 'Order Now',
        'hero_button_link' => '#',
        'favicon_url' => '',
        'background_pattern' => 'none',
        'primary_color' => '#667eea',
        'secondary_color' => '#764ba2',
        'accent_color' => '#ff6b6b',
        'theme_mode' => 'light',
        'current_theme' => 'default',
        'card_style' => 'elevated',
        'card_shadow' => 'light',
        'card_border_radius' => '10px',
        'navbar_bg' => '#ffffff',
        'navbar_text' => '#333333',
        'footer_bg' => '#1a1a2e',
        'footer_text' => '#aaaaaa'
    ];
} catch (Exception $e) {
    $settings = [
        'site_name' => 'ORDIVO',
        'site_tagline' => 'Fast Delivery • Fresh Products • Best Prices',
        'logo_url' => '🍔',
        'hero_background_image' => '',
        'hero_title' => 'Welcome to ORDIVO',
        'hero_subtitle' => 'Fast Delivery • Fresh Products • Best Prices',
        'hero_button_text' => 'Order Now',
        'hero_button_link' => '#',
        'favicon_url' => '',
        'background_pattern' => 'none',
        'primary_color' => '#667eea',
        'secondary_color' => '#764ba2',
        'accent_color' => '#ff6b6b',
        'theme_mode' => 'light',
        'current_theme' => 'default',
        'card_style' => 'elevated',
        'card_shadow' => 'light',
        'card_border_radius' => '10px',
        'navbar_bg' => '#ffffff',
        'navbar_text' => '#333333',
        'footer_bg' => '#1a1a2e',
        'footer_text' => '#aaaaaa'
    ];
}

// Get contact settings
try {
    $contactSettings = fetchRow("SELECT * FROM contact_settings LIMIT 1") ?: [
        'contact_email' => '',
        'contact_phone' => '',
        'business_address' => '',
        'support_email' => '',
        'sales_phone' => '',
        'whatsapp_number' => '',
        'facebook_url' => '',
        'instagram_url' => '',
        'twitter_url' => ''
    ];
} catch (Exception $e) {
    $contactSettings = [
        'contact_email' => '',
        'contact_phone' => '',
        'business_address' => '',
        'support_email' => '',
        'sales_phone' => '',
        'whatsapp_number' => '',
        'facebook_url' => '',
        'instagram_url' => '',
        'twitter_url' => ''
    ];
}

// Get layout settings
try {
    $layoutSettings = fetchRow("SELECT * FROM layout_settings LIMIT 1") ?: [
        'header_layout' => 'default',
        'footer_layout' => 'default',
        'show_search_bar' => 1,
        'show_social_links' => 1,
        'show_newsletter_signup' => 1
    ];
} catch (Exception $e) {
    $layoutSettings = [
        'header_layout' => 'default',
        'footer_layout' => 'default',
        'show_search_bar' => 1,
        'show_social_links' => 1,
        'show_newsletter_signup' => 1
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - ORDIVO Admin</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --ordivo-primary: #10b981;
            --ordivo-secondary: #059669;
            --ordivo-light: #f0fdf4;
            --ordivo-dark: #374151;
            --ordivo-accent: #f97316;
            --sidebar-width: 280px;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            margin: 0;
            padding: 0;
        }

        /* Mobile First - Sidebar hidden by default on mobile */
        .sidebar {
            position: fixed;
            top: 0;
            left: -280px; /* Hidden by default on mobile */
            height: 100vh;
            width: var(--sidebar-width);
            background: linear-gradient(180deg, #10b981 0%, #059669 100%);
            color: white;
            z-index: 1000;
            overflow-y: auto;
            transition: left 0.3s ease;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
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

        /* Mobile toggle button - Hidden, using inline version */
        .sidebar-toggle {
            display: none;
        }

        .sidebar-header {
            padding: 2rem 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.2);
            text-align: center;
        }

        .sidebar-brand {
            font-size: 1.8rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            color: white;
            text-decoration: none;
            display: block;
        }

        .sidebar-subtitle {
            font-size: 0.9rem;
            opacity: 0.8;
        }

        .sidebar-nav {
            padding: 1rem 0;
        }

        .nav-item {
            margin: 0.25rem 1rem;
        }

        .nav-link {
            color: #ffffff;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
        }

        .nav-link:hover, .nav-link.active {
            background: rgba(255, 255, 255, 0.2);
            color: #ffffff;
            transform: translateX(5px);
        }

        .nav-link i {
            width: 20px;
            margin-right: 0.75rem;
        }

        /* Main Content */
        .main-content {
            margin-left: 0; /* No margin on mobile */
            min-height: 100vh;
            padding: 0.625rem 1rem 1rem; /* 10px top, 1rem sides and bottom */
        }

        .page-header {
            background: white;
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-top: 4px solid #10b981;
            display: flex;
            flex-direction: row;
            gap: 0.75rem;
            align-items: center;
        }

        /* Inline hamburger button for mobile */
        .sidebar-toggle-inline {
            display: block;
            width: 40px;
            height: 40px;
            background: #10b981;
            border: none;
            border-radius: 8px;
            color: white;
            font-size: 1.1rem;
            cursor: pointer;
            flex-shrink: 0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            transition: all 0.3s ease;
        }

        .sidebar-toggle-inline:hover {
            background: #059669;
            transform: scale(1.05);
        }

        .page-header-content {
            flex: 1;
            min-width: 0;
        }

        .page-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--ordivo-dark);
            margin: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .page-subtitle {
            font-size: 0.75rem;
            color: #6c757d;
            margin: 0;
            display: none; /* Hide subtitle on mobile */
        }

        .btn-primary {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border: none;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
        }

        .nav-pills .nav-link {
            border-radius: 8px;
            margin-bottom: 0.5rem;
            color: #6c757d;
            font-weight: 500;
        }

        .nav-pills .nav-link.active {
            background: #10b981; 100%);
            color: white;
        }

        .nav-pills .nav-link:hover:not(.active) {
            background: var(--ordivo-light);
            color: var(--ordivo-primary);
        }

        .form-control, .form-select {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 0.75rem;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--ordivo-primary);
            box-shadow: 0 0 0 0.2rem #f97316;
        }

        .form-check-input:checked {
            background-color: var(--ordivo-primary);
            border-color: var(--ordivo-primary);
        }

        .settings-section {
            display: none;
        }

        .settings-section.active {
            display: block;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--ordivo-dark);
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--ordivo-light);
        }

        .setting-group {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .setting-group h6 {
            color: var(--ordivo-primary);
            font-weight: 600;
            margin-bottom: 1rem;
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
                margin-left: var(--sidebar-width);
                padding: 1.5rem;
            }

            .page-header {
                padding: 1.5rem;
                margin-bottom: 2rem;
            }

            .page-title {
                font-size: 1.8rem;
                white-space: normal;
            }

            .page-subtitle {
                font-size: 1rem;
                display: block; /* Show subtitle on tablet+ */
            }
        }

        /* Desktop */
        @media (min-width: 1200px) {
            .main-content {
                padding: 2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-brand">
                <?php 
                $settings = fetchRow("SELECT * FROM site_settings LIMIT 1");
                $sidebarLogoUrl = $settings['logo_url'] ?? '';
                
                // Fix path for super_admin directory
                if (!empty($sidebarLogoUrl)) {
                    if (strpos($sidebarLogoUrl, 'uploads/') === 0) {
                        $sidebarLogoUrl = '../' . $sidebarLogoUrl;
                    }
                }
                ?>
                
                <?php if (!empty($sidebarLogoUrl)): ?>
                    <img src="<?= htmlspecialchars($sidebarLogoUrl) ?>" alt="ORDIVO" 
                         style="height: 90px; width: auto; vertical-align: middle;"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='inline';">
                    <i class="fas fa-utensils" style="display: none; font-size: 2rem;"></i>
                <?php else: ?>
                    <i class="fas fa-utensils" style="font-size: 2rem;"></i>
                <?php endif; ?>
            </div>
            <div class="sidebar-subtitle">Super Admin Panel</div>
        </div>
        
        <nav class="sidebar-nav">
            <div class="nav-item">
                <a href="dashboard.php" class="nav-link">
                    <i class="fas fa-tachometer-alt"></i>Dashboard
                </a>
            </div>
            <div class="nav-item">
                <a href="users.php" class="nav-link">
                    <i class="fas fa-users"></i>User Management
                </a>
            </div>
            <div class="nav-item">
                <a href="vendors.php" class="nav-link">
                    <i class="fas fa-store"></i>Vendor Management
                </a>
            </div>
            <div class="nav-item">
                <a href="products_featured.php" class="nav-link">
                    <i class="fas fa-star"></i>Featured Products
                </a>
            </div>
            <div class="nav-item">
                <a href="categories.php" class="nav-link">
                    <i class="fas fa-tags"></i>Categories
                </a>
            </div>
            <div class="nav-item">
                <a href="orders.php" class="nav-link">
                    <i class="fas fa-shopping-cart"></i>Orders
                </a>
            </div>
            <div class="nav-item">
                <a href="analytics.php" class="nav-link">
                    <i class="fas fa-chart-bar"></i>Analytics
                </a>
            </div>
            <div class="nav-item">
                <a href="settings.php" class="nav-link active">
                    <i class="fas fa-cog"></i>Settings
                </a>
            </div>
            <div class="nav-item mt-4">
                <a href="../auth/logout.php" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i>Logout
                </a>
            </div>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <button class="sidebar-toggle-inline" id="sidebarToggleInline">
                <i class="fas fa-bars"></i>
            </button>
            <div class="page-header-content">
                <h1 class="page-title">
                    <i class="fas fa-cog me-2"></i>System Settings
                </h1>
                <p class="page-subtitle">Configure platform settings and preferences</p>
            </div>
        </div>

        <!-- Alerts -->
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Settings Navigation -->
            <div class="col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <!-- Mobile toggle for settings nav -->
                        <button class="btn btn-primary w-100 mb-3 d-lg-none" type="button" data-bs-toggle="collapse" data-bs-target="#settingsNav" aria-expanded="false" aria-controls="settingsNav">
                            <i class="fas fa-bars me-2"></i>Settings Menu
                        </button>
                        
                        <div class="collapse d-lg-block" id="settingsNav">
                            <div class="nav flex-column nav-pills" role="tablist">
                                <button class="nav-link active" onclick="showSection('general')">
                                    <i class="fas fa-info-circle me-2"></i>General Settings
                                </button>
                                <button class="nav-link" onclick="showSection('hero')">
                                    <i class="fas fa-image me-2"></i>Hero Section
                                </button>
                                <button class="nav-link" onclick="showSection('images')">
                                    <i class="fas fa-images me-2"></i>Image Manager
                                </button>
                                <button class="nav-link" onclick="showSection('contact')">
                                    <i class="fas fa-address-book me-2"></i>Contact Information
                                </button>
                                <button class="nav-link" onclick="showSection('layout')">
                                    <i class="fas fa-layout me-2"></i>Page Layout
                                </button>
                                <button class="nav-link" onclick="showSection('theme')">
                                    <i class="fas fa-palette me-2"></i>Theme Settings
                                </button>
                                <button class="nav-link" onclick="showSection('ui')">
                                    <i class="fas fa-desktop me-2"></i>UI Settings
                                </button>
                                <button class="nav-link" onclick="showSection('advanced')">
                                    <i class="fas fa-cogs me-2"></i>Advanced Editor
                                </button>
                                <button class="nav-link" onclick="showSection('advanced')">
                                    <i class="fas fa-cogs me-2"></i>Advanced Editor
                                </button>
                                <button class="nav-link" onclick="showSection('security')">
                                    <i class="fas fa-shield-alt me-2"></i>Security
                                </button>
                                <button class="nav-link" onclick="showSection('maintenance')">
                                    <i class="fas fa-tools me-2"></i>Maintenance
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Settings Content -->
            <div class="col-lg-9">
                <!-- General Settings -->
                <div id="general-section" class="settings-section active">
                    <div class="card">
                        <div class="card-body">
                            <h3 class="section-title">
                                <i class="fas fa-info-circle me-2"></i>General Settings
                            </h3>
                            
                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="update_general">
                                
                                <div class="setting-group">
                                    <h6>Site Information</h6>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="site_name" class="form-label">Site Name</label>
                                            <input type="text" class="form-control" id="site_name" name="site_name" 
                                                   value="<?= htmlspecialchars($settings['site_name'] ?? 'ORDIVO') ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="site_tagline" class="form-label">Site Tagline</label>
                                            <input type="text" class="form-control" id="site_tagline" name="site_tagline" 
                                                   value="<?= htmlspecialchars($settings['site_tagline'] ?? 'Fast Delivery • Fresh Products • Best Prices') ?>" 
                                                   placeholder="Fast Delivery • Fresh Products • Best Prices">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="setting-group">
                                    <h6>Logo & Branding</h6>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="logo_url" class="form-label">Logo URL (or upload below)</label>
                                            <input type="text" class="form-control" id="logo_url" name="logo_url" 
                                                   value="<?= htmlspecialchars($settings['logo_url'] ?? '🍔') ?>" 
                                                   placeholder="🍔 or URL">
                                            <?php if (!empty($settings['logo_url']) && $settings['logo_url'] !== '🍔'): ?>
                                                <div class="mt-2">
                                                    <?php 
                                                    $displayLogoUrl = $settings['logo_url'];
                                                    // Fix path for super_admin directory - add ../ prefix if it's a relative path
                                                    if (strpos($displayLogoUrl, 'uploads/') === 0) {
                                                        $displayLogoUrl = '../' . $displayLogoUrl;
                                                    }
                                                    ?>
                                                    <img src="<?= htmlspecialchars($displayLogoUrl) ?>" alt="Current Logo" 
                                                         style="max-width: 100px; max-height: 50px; object-fit: contain;"
                                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                                    <div style="display: none; color: #dc3545; font-size: 0.875rem;">
                                                        <i class="fas fa-exclamation-triangle"></i> Logo file not found
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="logo_upload" class="form-label">Upload New Logo</label>
                                            <input type="file" class="form-control" id="logo_upload" name="logo_upload" 
                                                   accept="image/*" onchange="previewImage(this, 'logo-preview')">
                                            <div id="logo-preview" class="mt-2"></div>
                                            <small class="text-muted">Recommended: PNG/SVG, max 5MB</small>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="favicon_url" class="form-label">Favicon URL (or upload below)</label>
                                            <input type="text" class="form-control" id="favicon_url" name="favicon_url" 
                                                   value="<?= htmlspecialchars($settings['favicon_url'] ?? '') ?>" 
                                                   placeholder="favicon.ico URL">
                                            <?php if (!empty($settings['favicon_url'])): ?>
                                                <div class="mt-2">
                                                    <img src="<?= htmlspecialchars($settings['favicon_url']) ?>" alt="Current Favicon" 
                                                         style="width: 32px; height: 32px; object-fit: contain;">
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="favicon_upload" class="form-label">Upload New Favicon</label>
                                            <input type="file" class="form-control" id="favicon_upload" name="favicon_upload" 
                                                   accept="image/*,.ico" onchange="previewImage(this, 'favicon-preview')">
                                            <div id="favicon-preview" class="mt-2"></div>
                                            <small class="text-muted">Recommended: ICO/PNG, 32x32px</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Save General Settings
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Hero Section -->
                <div id="hero-section" class="settings-section">
                    <div class="card">
                        <div class="card-body">
                            <h3 class="section-title">
                                <i class="fas fa-image me-2"></i>Hero Section Settings
                            </h3>
                            
                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="update_hero">
                                
                                <div class="setting-group">
                                    <h6>Hero Content</h6>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="hero_title" class="form-label">Hero Title</label>
                                            <input type="text" class="form-control" id="hero_title" name="hero_title" 
                                                   value="<?= htmlspecialchars($settings['hero_title'] ?? 'Welcome to ORDIVO') ?>" 
                                                   placeholder="Welcome to ORDIVO">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="hero_button_text" class="form-label">Button Text</label>
                                            <input type="text" class="form-control" id="hero_button_text" name="hero_button_text" 
                                                   value="<?= htmlspecialchars($settings['hero_button_text'] ?? 'Order Now') ?>" 
                                                   placeholder="Order Now">
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="hero_subtitle" class="form-label">Hero Subtitle</label>
                                        <textarea class="form-control" id="hero_subtitle" name="hero_subtitle" rows="2" 
                                                  placeholder="Fast Delivery • Fresh Products • Best Prices"><?= htmlspecialchars($settings['hero_subtitle'] ?? 'Fast Delivery • Fresh Products • Best Prices') ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="hero_button_link" class="form-label">Button Link</label>
                                        <input type="url" class="form-control" id="hero_button_link" name="hero_button_link" 
                                               value="<?= htmlspecialchars($settings['hero_button_link'] ?? '#') ?>" 
                                               placeholder="#vendors or full URL">
                                    </div>
                                </div>
                                
                                <div class="setting-group">
                                    <h6>Hero Background</h6>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="hero_background_image" class="form-label">Background Image URL</label>
                                            <input type="text" class="form-control" id="hero_background_image" name="hero_background_image" 
                                                   value="<?= htmlspecialchars($settings['hero_background_image'] ?? '') ?>" 
                                                   placeholder="Background image URL">
                                            <?php if (!empty($settings['hero_background_image'])): ?>
                                                <div class="mt-2">
                                                    <img src="<?= htmlspecialchars($settings['hero_background_image']) ?>" alt="Current Hero Background" 
                                                         style="max-width: 200px; max-height: 100px; object-fit: cover; border-radius: 8px;">
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="hero_bg_upload" class="form-label">Upload New Background</label>
                                            <input type="file" class="form-control" id="hero_bg_upload" name="hero_bg_upload" 
                                                   accept="image/*" onchange="previewImage(this, 'hero-bg-preview')">
                                            <div id="hero-bg-preview" class="mt-2"></div>
                                            <small class="text-muted">Recommended: 1920x1080px, max 10MB</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Save Hero Section
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Image Manager -->
                <div id="images-section" class="settings-section">
                    <div class="card">
                        <div class="card-body">
                            <h3 class="section-title">
                                <i class="fas fa-images me-2"></i>Image Manager
                            </h3>
                            
                            <div class="setting-group">
                                <h6>Upload New Image</h6>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="image_type" class="form-label">Image Type</label>
                                        <select class="form-select" id="image_type" name="image_type">
                                            <option value="logo">Logo</option>
                                            <option value="hero">Hero Section</option>
                                            <option value="background">Background</option>
                                            <option value="banner">Banner</option>
                                            <option value="icon">Icon</option>
                                            <option value="gallery">Gallery</option>
                                            <option value="other">Other</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="image_upload" class="form-label">Select Image</label>
                                        <input type="file" class="form-control" id="image_upload" name="image_upload" 
                                               accept="image/*" onchange="previewImage(this, 'upload-preview')">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="alt_text" class="form-label">Alt Text</label>
                                        <input type="text" class="form-control" id="alt_text" name="alt_text" 
                                               placeholder="Describe the image">
                                    </div>
                                </div>
                                
                                <div id="upload-preview" class="mb-3"></div>
                                
                                <button type="button" class="btn btn-success" onclick="uploadImage()">
                                    <i class="fas fa-upload me-2"></i>Upload Image
                                </button>
                            </div>
                            
                            <div class="setting-group">
                                <h6>Existing Images</h6>
                                <div class="row" id="images-gallery">
                                    <div class="col-12 text-center">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                        <p class="mt-2">Loading images...</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Advanced Editor -->
                <div id="advanced-section" class="settings-section">
                    <div class="card">
                        <div class="card-body">
                            <h3 class="section-title">
                                <i class="fas fa-cogs me-2"></i>Advanced Site Editor
                            </h3>
                            
                            <div class="setting-group">
                                <h6>Direct Field Editor</h6>
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>Advanced Feature:</strong> Edit any field in the database directly. Use with caution.
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="edit_table" class="form-label">Table</label>
                                        <select class="form-select" id="edit_table" onchange="loadTableFields()">
                                            <option value="">Select Table</option>
                                            <option value="site_settings">Site Settings</option>
                                            <option value="contact_settings">Contact Settings</option>
                                            <option value="layout_settings">Layout Settings</option>
                                            <option value="users">Users</option>
                                            <option value="vendors">Vendors</option>
                                            <option value="categories">Categories</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="edit_field" class="form-label">Field</label>
                                        <select class="form-select" id="edit_field" disabled>
                                            <option value="">Select Field</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="edit_record_id" class="form-label">Record ID</label>
                                        <input type="number" class="form-control" id="edit_record_id" value="1" min="1">
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="edit_value" class="form-label">New Value</label>
                                    <textarea class="form-control" id="edit_value" rows="3" 
                                              placeholder="Enter the new value for the selected field"></textarea>
                                </div>
                                
                                <button type="button" class="btn btn-warning" onclick="updateField()">
                                    <i class="fas fa-edit me-2"></i>Update Field
                                </button>
                            </div>
                            
                            <div class="setting-group">
                                <h6>Custom CSS/JS</h6>
                                <div class="mb-3">
                                    <label for="custom_css" class="form-label">Custom CSS</label>
                                    <textarea class="form-control" id="custom_css" rows="5" 
                                              placeholder="/* Add your custom CSS here */"></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="custom_js" class="form-label">Custom JavaScript</label>
                                    <textarea class="form-control" id="custom_js" rows="5" 
                                              placeholder="// Add your custom JavaScript here"></textarea>
                                </div>
                                
                                <button type="button" class="btn btn-primary" onclick="saveCustomCode()">
                                    <i class="fas fa-save me-2"></i>Save Custom Code
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contact Information -->
                <div id="contact-section" class="settings-section">
                    <div class="card">
                        <div class="card-body">
                            <h3 class="section-title">
                                <i class="fas fa-address-book me-2"></i>Contact Information
                            </h3>
                            
                            <form method="POST">
                                <input type="hidden" name="action" value="update_contact">
                                
                                <div class="setting-group">
                                    <h6>Primary Contact</h6>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="contact_email" class="form-label">Contact Email</label>
                                            <input type="email" class="form-control" id="contact_email" name="contact_email" 
                                                   value="<?= htmlspecialchars($contactSettings['contact_email'] ?? '') ?>" 
                                                   placeholder="info@ordivo.com">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="contact_phone" class="form-label">Contact Phone</label>
                                            <input type="tel" class="form-control" id="contact_phone" name="contact_phone" 
                                                   value="<?= htmlspecialchars($contactSettings['contact_phone'] ?? '') ?>" 
                                                   placeholder="+880 1712-345678">
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="business_address" class="form-label">Business Address</label>
                                        <textarea class="form-control" id="business_address" name="business_address" rows="3" 
                                                  placeholder="Enter your complete business address"><?= htmlspecialchars($contactSettings['business_address'] ?? '') ?></textarea>
                                    </div>
                                </div>
                                
                                <div class="setting-group">
                                    <h6>Support & Sales</h6>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="support_email" class="form-label">Support Email</label>
                                            <input type="email" class="form-control" id="support_email" name="support_email" 
                                                   value="<?= htmlspecialchars($contactSettings['support_email'] ?? '') ?>" 
                                                   placeholder="support@ordivo.com">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="sales_phone" class="form-label">Sales Phone</label>
                                            <input type="tel" class="form-control" id="sales_phone" name="sales_phone" 
                                                   value="<?= htmlspecialchars($contactSettings['sales_phone'] ?? '') ?>" 
                                                   placeholder="+880 1712-345679">
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="whatsapp_number" class="form-label">WhatsApp Number</label>
                                        <input type="tel" class="form-control" id="whatsapp_number" name="whatsapp_number" 
                                               value="<?= htmlspecialchars($contactSettings['whatsapp_number'] ?? '') ?>" 
                                               placeholder="+880 1712-345678">
                                    </div>
                                </div>
                                
                                <div class="setting-group">
                                    <h6>Social Media Links</h6>
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label for="facebook_url" class="form-label">Facebook URL</label>
                                            <input type="url" class="form-control" id="facebook_url" name="facebook_url" 
                                                   value="<?= htmlspecialchars($contactSettings['facebook_url'] ?? '') ?>" 
                                                   placeholder="https://facebook.com/ordivo">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="instagram_url" class="form-label">Instagram URL</label>
                                            <input type="url" class="form-control" id="instagram_url" name="instagram_url" 
                                                   value="<?= htmlspecialchars($contactSettings['instagram_url'] ?? '') ?>" 
                                                   placeholder="https://instagram.com/ordivo">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="twitter_url" class="form-label">Twitter URL</label>
                                            <input type="url" class="form-control" id="twitter_url" name="twitter_url" 
                                                   value="<?= htmlspecialchars($contactSettings['twitter_url'] ?? '') ?>" 
                                                   placeholder="https://twitter.com/ordivo">
                                        </div>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Save Contact Information
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Page Layout -->
                <div id="layout-section" class="settings-section">
                    <div class="card">
                        <div class="card-body">
                            <h3 class="section-title">
                                <i class="fas fa-layout me-2"></i>Page Layout Settings
                            </h3>
                            
                            <form method="POST">
                                <input type="hidden" name="action" value="update_layout">
                                
                                <div class="setting-group">
                                    <h6>Header Configuration</h6>
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label for="header_layout" class="form-label">Header Layout</label>
                                            <select class="form-select" id="header_layout" name="header_layout">
                                                <option value="default" <?= ($layoutSettings['header_layout'] ?? 'default') === 'default' ? 'selected' : '' ?>>Default</option>
                                                <option value="centered" <?= ($layoutSettings['header_layout'] ?? 'default') === 'centered' ? 'selected' : '' ?>>Centered</option>
                                                <option value="minimal" <?= ($layoutSettings['header_layout'] ?? 'default') === 'minimal' ? 'selected' : '' ?>>Minimal</option>
                                                <option value="extended" <?= ($layoutSettings['header_layout'] ?? 'default') === 'extended' ? 'selected' : '' ?>>Extended</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="header_bg_color" class="form-label">Header Background</label>
                                            <input type="color" class="form-control form-control-color" id="header_bg_color" name="header_bg_color" 
                                                   value="<?= $settings['navbar_bg'] ?? '#ffffff' ?>">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="header_text_color" class="form-label">Header Text Color</label>
                                            <input type="color" class="form-control form-control-color" id="header_text_color" name="header_text_color" 
                                                   value="<?= $settings['navbar_text'] ?? '#333333' ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="show_search_bar" name="show_search_bar" 
                                               <?= ($layoutSettings['show_search_bar'] ?? 1) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="show_search_bar">
                                            <strong>Show Search Bar in Header</strong><br>
                                            <small class="text-muted">Display search functionality in the header</small>
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="setting-group">
                                    <h6>Footer Configuration</h6>
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label for="footer_layout" class="form-label">Footer Layout</label>
                                            <select class="form-select" id="footer_layout" name="footer_layout">
                                                <option value="default" <?= ($layoutSettings['footer_layout'] ?? 'default') === 'default' ? 'selected' : '' ?>>Default</option>
                                                <option value="minimal" <?= ($layoutSettings['footer_layout'] ?? 'default') === 'minimal' ? 'selected' : '' ?>>Minimal</option>
                                                <option value="extended" <?= ($layoutSettings['footer_layout'] ?? 'default') === 'extended' ? 'selected' : '' ?>>Extended</option>
                                                <option value="columns" <?= ($layoutSettings['footer_layout'] ?? 'default') === 'columns' ? 'selected' : '' ?>>Multi-Column</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="footer_bg_color" class="form-label">Footer Background</label>
                                            <input type="color" class="form-control form-control-color" id="footer_bg_color" name="footer_bg_color" 
                                                   value="<?= $settings['footer_bg'] ?? '#1a1a2e' ?>">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="footer_text_color" class="form-label">Footer Text Color</label>
                                            <input type="color" class="form-control form-control-color" id="footer_text_color" name="footer_text_color" 
                                                   value="<?= $settings['footer_text'] ?? '#aaaaaa' ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-check mb-3">
                                                <input class="form-check-input" type="checkbox" id="show_social_links" name="show_social_links" 
                                                       <?= ($layoutSettings['show_social_links'] ?? 1) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="show_social_links">
                                                    <strong>Show Social Media Links</strong><br>
                                                    <small class="text-muted">Display social media icons in footer</small>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check mb-3">
                                                <input class="form-check-input" type="checkbox" id="show_newsletter_signup" name="show_newsletter_signup" 
                                                       <?= ($layoutSettings['show_newsletter_signup'] ?? 1) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="show_newsletter_signup">
                                                    <strong>Show Newsletter Signup</strong><br>
                                                    <small class="text-muted">Display newsletter subscription form</small>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="setting-group">
                                    <h6>Page Sections</h6>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <strong>Advanced Layout Editor</strong><br>
                                        Use the layout editor to customize individual page sections, add custom content blocks, and modify page structures.
                                    </div>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <button type="button" class="btn btn-outline-primary mb-2" onclick="openLayoutEditor('homepage')">
                                            <i class="fas fa-edit me-2"></i>Edit Homepage Layout
                                        </button>
                                        <button type="button" class="btn btn-outline-primary mb-2" onclick="openLayoutEditor('vendor-page')">
                                            <i class="fas fa-store me-2"></i>Edit Vendor Page Layout
                                        </button>
                                        <button type="button" class="btn btn-outline-primary mb-2" onclick="openLayoutEditor('product-page')">
                                            <i class="fas fa-box me-2"></i>Edit Product Page Layout
                                        </button>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Save Layout Settings
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Theme Settings -->
                <div id="theme-section" class="settings-section">
                    <div class="card">
                        <div class="card-body">
                            <h3 class="section-title">
                                <i class="fas fa-palette me-2"></i>Theme Settings
                            </h3>
                            
                            <form method="POST">
                                <input type="hidden" name="action" value="update_business">
                                
                                <div class="setting-group">
                                    <h6>Color Scheme</h6>
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label for="primary_color" class="form-label">Primary Color</label>
                                            <input type="color" class="form-control form-control-color" id="primary_color" name="primary_color" 
                                                   value="<?= $settings['primary_color'] ?? '#667eea' ?>">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="secondary_color" class="form-label">Secondary Color</label>
                                            <input type="color" class="form-control form-control-color" id="secondary_color" name="secondary_color" 
                                                   value="<?= $settings['secondary_color'] ?? '#764ba2' ?>">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="accent_color" class="form-label">Accent Color</label>
                                            <input type="color" class="form-control form-control-color" id="accent_color" name="accent_color" 
                                                   value="<?= $settings['accent_color'] ?? '#ff6b6b' ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="setting-group">
                                    <h6>Theme Mode</h6>
                                    <div class="mb-3">
                                        <select class="form-select" id="theme_mode" name="theme_mode">
                                            <option value="light" <?= ($settings['theme_mode'] ?? 'light') === 'light' ? 'selected' : '' ?>>Light Mode</option>
                                            <option value="dark" <?= ($settings['theme_mode'] ?? 'light') === 'dark' ? 'selected' : '' ?>>Dark Mode</option>
                                            <option value="auto" <?= ($settings['theme_mode'] ?? 'light') === 'auto' ? 'selected' : '' ?>>Auto (System)</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Save Theme Settings
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- UI Settings -->
                <div id="ui-section" class="settings-section">
                    <div class="card">
                        <div class="card-body">
                            <h3 class="section-title">
                                <i class="fas fa-desktop me-2"></i>UI Settings
                            </h3>
                            
                            <form method="POST">
                                <input type="hidden" name="action" value="update_notifications">
                                
                                <div class="setting-group">
                                    <h6>Card Appearance</h6>
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label for="card_style" class="form-label">Card Style</label>
                                            <select class="form-select" id="card_style" name="card_style">
                                                <option value="flat" <?= ($settings['card_style'] ?? 'elevated') === 'flat' ? 'selected' : '' ?>>Flat</option>
                                                <option value="elevated" <?= ($settings['card_style'] ?? 'elevated') === 'elevated' ? 'selected' : '' ?>>Elevated</option>
                                                <option value="outlined" <?= ($settings['card_style'] ?? 'elevated') === 'outlined' ? 'selected' : '' ?>>Outlined</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="card_shadow" class="form-label">Card Shadow</label>
                                            <select class="form-select" id="card_shadow" name="card_shadow">
                                                <option value="none" <?= ($settings['card_shadow'] ?? 'light') === 'none' ? 'selected' : '' ?>>None</option>
                                                <option value="light" <?= ($settings['card_shadow'] ?? 'light') === 'light' ? 'selected' : '' ?>>Light</option>
                                                <option value="medium" <?= ($settings['card_shadow'] ?? 'light') === 'medium' ? 'selected' : '' ?>>Medium</option>
                                                <option value="heavy" <?= ($settings['card_shadow'] ?? 'light') === 'heavy' ? 'selected' : '' ?>>Heavy</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="card_border_radius" class="form-label">Border Radius</label>
                                            <input type="text" class="form-control" id="card_border_radius" name="card_border_radius" 
                                                   value="<?= htmlspecialchars($settings['card_border_radius'] ?? '10px') ?>" 
                                                   placeholder="10px">
                                        </div>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Save UI Settings
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Security -->
                <div id="security-section" class="settings-section">
                    <div class="card">
                        <div class="card-body">
                            <h3 class="section-title">
                                <i class="fas fa-shield-alt me-2"></i>Security Settings
                            </h3>
                            
                            <div class="setting-group">
                                <h6>Password Policy</h6>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Current password policy: Minimum 8 characters required
                                </div>
                            </div>
                            
                            <div class="setting-group">
                                <h6>Session Management</h6>
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle me-2"></i>
                                    Session security is enabled with automatic regeneration
                                </div>
                            </div>
                            
                            <div class="setting-group">
                                <h6>Rate Limiting</h6>
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle me-2"></i>
                                    Rate limiting is active for login and registration attempts
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Maintenance -->
                <div id="maintenance-section" class="settings-section">
                    <div class="card">
                        <div class="card-body">
                            <h3 class="section-title">
                                <i class="fas fa-tools me-2"></i>Maintenance & System
                            </h3>
                            
                            <div class="setting-group">
                                <h6>System Information</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>PHP Version:</strong> <?= PHP_VERSION ?></p>
                                        <p><strong>Platform:</strong> ORDIVO v1.0.0</p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Database:</strong> MySQL</p>
                                        <p><strong>Environment:</strong> <?= ENVIRONMENT ?></p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="setting-group">
                                <h6>Database Status</h6>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="mb-1"><strong>Connection Status</strong></p>
                                        <small class="text-muted">Database connection is active</small>
                                    </div>
                                    <span class="badge bg-success">Connected</span>
                                </div>
                            </div>
                            
                            <div class="setting-group">
                                <h6>Cache & Performance</h6>
                                <button class="btn btn-outline-primary me-2">
                                    <i class="fas fa-sync-alt me-2"></i>Clear Cache
                                </button>
                                <button class="btn btn-outline-info">
                                    <i class="fas fa-chart-line me-2"></i>Performance Report
                                </button>
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
        // Mobile menu toggle
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        const sidebarToggleInline = document.getElementById('sidebarToggleInline');

        function toggleSidebar() {
            sidebar.classList.toggle('show');
            sidebarOverlay.classList.toggle('show');
        }

        if (sidebarToggleInline) {
            sidebarToggleInline.addEventListener('click', toggleSidebar);
        }

        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', toggleSidebar);
        }

        function showSection(sectionName) {
            // Hide all sections
            document.querySelectorAll('.settings-section').forEach(section => {
                section.classList.remove('active');
            });
            
            // Show selected section
            const targetSection = document.getElementById(sectionName + '-section');
            if (targetSection) {
                targetSection.classList.add('active');
            }
            
            // Update navigation
            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.remove('active');
            });
            event.target.classList.add('active');
            
            // Auto-collapse settings nav on mobile after selection
            if (window.innerWidth < 992) {
                const settingsNav = document.getElementById('settingsNav');
                if (settingsNav && settingsNav.classList.contains('show')) {
                    const bsCollapse = new bootstrap.Collapse(settingsNav, {
                        toggle: true
                    });
                }
            }
            
            // Load images if image manager section is shown
            if (sectionName === 'images') {
                loadImages();
            }
        }
        
        function previewImage(input, previewId) {
            const preview = document.getElementById(previewId);
            preview.innerHTML = '';
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.style.maxWidth = '200px';
                    img.style.maxHeight = '100px';
                    img.style.objectFit = 'contain';
                    img.style.borderRadius = '8px';
                    img.style.border = '1px solid #ddd';
                    preview.appendChild(img);
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        function uploadImage() {
            const imageType = document.getElementById('image_type').value;
            const imageFile = document.getElementById('image_upload').files[0];
            const altText = document.getElementById('alt_text').value;
            
            if (!imageFile) {
                alert('Please select an image to upload.');
                return;
            }
            
            const formData = new FormData();
            formData.append('ajax_action', 'upload_image');
            formData.append('image_type', imageType);
            formData.append('image', imageFile);
            formData.append('alt_text', altText);
            
            fetch('image_manager.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Image uploaded successfully!');
                    document.getElementById('image_upload').value = '';
                    document.getElementById('alt_text').value = '';
                    document.getElementById('upload-preview').innerHTML = '';
                    loadImages();
                } else {
                    alert('Upload failed: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Upload failed. Please try again.');
            });
        }
        
        function loadImages() {
            const gallery = document.getElementById('images-gallery');
            gallery.innerHTML = '<div class="col-12 text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><p class="mt-2">Loading images...</p></div>';
            
            const formData = new FormData();
            formData.append('ajax_action', 'get_images');
            formData.append('image_type', 'all');
            
            fetch('image_manager.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayImages(data.images);
                } else {
                    gallery.innerHTML = '<div class="col-12 text-center text-muted">No images found.</div>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                gallery.innerHTML = '<div class="col-12 text-center text-danger">Failed to load images.</div>';
            });
        }
        
        function displayImages(images) {
            const gallery = document.getElementById('images-gallery');
            
            if (images.length === 0) {
                gallery.innerHTML = '<div class="col-12 text-center text-muted">No images uploaded yet.</div>';
                return;
            }
            
            let html = '';
            images.forEach(image => {
                html += `
                    <div class="col-md-3 mb-3">
                        <div class="card">
                            <img src="${image.image_path}" class="card-img-top" style="height: 150px; object-fit: cover;" alt="${image.alt_text || image.image_name}">
                            <div class="card-body p-2">
                                <h6 class="card-title small">${image.image_name}</h6>
                                <p class="card-text small text-muted">Type: ${image.image_type}</p>
                                <p class="card-text small text-muted">${image.width}x${image.height}px</p>
                                <div class="d-flex gap-1">
                                    <button class="btn btn-sm btn-outline-primary" onclick="copyImageUrl('${image.image_path}')">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteImageConfirm(${image.id})">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            gallery.innerHTML = html;
        }
        
        function copyImageUrl(imagePath) {
            const fullUrl = window.location.origin + '/' + imagePath;
            navigator.clipboard.writeText(fullUrl).then(() => {
                alert('Image URL copied to clipboard!');
            });
        }
        
        function deleteImageConfirm(imageId) {
            if (confirm('Are you sure you want to delete this image? This action cannot be undone.')) {
                deleteImage(imageId);
            }
        }
        
        function deleteImage(imageId) {
            const formData = new FormData();
            formData.append('ajax_action', 'delete_image');
            formData.append('image_id', imageId);
            
            fetch('image_manager.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Image deleted successfully!');
                    loadImages();
                } else {
                    alert('Delete failed: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Delete failed. Please try again.');
            });
        }
        
        function loadTableFields() {
            const table = document.getElementById('edit_table').value;
            const fieldSelect = document.getElementById('edit_field');
            
            fieldSelect.innerHTML = '<option value="">Loading...</option>';
            fieldSelect.disabled = true;
            
            if (!table) {
                fieldSelect.innerHTML = '<option value="">Select Field</option>';
                return;
            }
            
            // Predefined field mappings for common tables
            const tableFields = {
                'site_settings': ['site_name', 'site_tagline', 'logo_url', 'hero_title', 'hero_subtitle', 'hero_button_text', 'hero_button_link', 'hero_background_image', 'favicon_url', 'primary_color', 'secondary_color', 'accent_color', 'theme_mode'],
                'contact_settings': ['contact_email', 'contact_phone', 'business_address', 'support_email', 'sales_phone', 'whatsapp_number', 'facebook_url', 'instagram_url', 'twitter_url'],
                'layout_settings': ['header_layout', 'footer_layout', 'show_search_bar', 'show_social_links', 'show_newsletter_signup'],
                'users': ['name', 'email', 'phone', 'status', 'role'],
                'vendors': ['name', 'description', 'address', 'phone', 'email', 'is_active', 'is_verified'],
                'categories': ['name', 'description', 'icon', 'sort_order', 'is_active']
            };
            
            const fields = tableFields[table] || [];
            let html = '<option value="">Select Field</option>';
            fields.forEach(field => {
                html += `<option value="${field}">${field.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}</option>`;
            });
            
            fieldSelect.innerHTML = html;
            fieldSelect.disabled = false;
        }
        
        function updateField() {
            const table = document.getElementById('edit_table').value;
            const field = document.getElementById('edit_field').value;
            const recordId = document.getElementById('edit_record_id').value;
            const value = document.getElementById('edit_value').value;
            
            if (!table || !field || !recordId) {
                alert('Please select table, field, and record ID.');
                return;
            }
            
            if (!confirm(`Are you sure you want to update ${field} in ${table} (ID: ${recordId})?`)) {
                return;
            }
            
            const formData = new FormData();
            formData.append('ajax_action', 'update_field');
            formData.append('table', table);
            formData.append('field', field);
            formData.append('record_id', recordId);
            formData.append('value', value);
            
            fetch('image_manager.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Field updated successfully!');
                    document.getElementById('edit_value').value = '';
                } else {
                    alert('Update failed: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Update failed. Please try again.');
            });
        }
        
        function saveCustomCode() {
            const css = document.getElementById('custom_css').value;
            const js = document.getElementById('custom_js').value;
            
            const formData = new FormData();
            formData.append('ajax_action', 'save_custom_code');
            formData.append('custom_css', css);
            formData.append('custom_js', js);
            
            fetch('image_manager.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Custom code saved successfully!');
                } else {
                    alert('Save failed: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Save failed. Please try again.');
            });
        }
        
        function openLayoutEditor(pageType) {
            alert(`Advanced Layout Editor for ${pageType} will be implemented in the next phase.\n\nThis will allow you to:\n• Drag and drop page sections\n• Customize content blocks\n• Modify page structures\n• Add custom HTML/CSS\n• Preview changes in real-time`);
        }
    </script>
    </div><!-- End Main Content -->
</body>
</html>