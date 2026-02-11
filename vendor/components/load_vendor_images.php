<?php
/**
 * ORDIVO - Load Vendor Images Helper
 * Loads vendor cover photo and user profile picture
 * Include this file after loading $vendorId and $vendor variables
 */

// Get vendor cover photo
$vendorCover = '';
if (isset($vendorInfo) && !empty($vendorInfo['banner_image'])) {
    $vendorCover = $vendorInfo['banner_image'];
    
    // Add ../ prefix if path starts with uploads/
    if (strpos($vendorCover, 'uploads/') === 0) {
        $vendorCover = '../' . $vendorCover;
    }
}

// Get user profile picture
$userAvatar = '';
if (isset($vendor) && !empty($vendor['avatar'])) {
    $userAvatar = $vendor['avatar'];
    
    // Add ../ prefix if path starts with uploads/
    if (strpos($userAvatar, 'uploads/') === 0) {
        $userAvatar = '../' . $userAvatar;
    }
}
?>
