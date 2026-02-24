<?php
/**
 * ORDIVO Image Optimizer
 * Automatically compress and optimize images
 */

/**
 * Check if GD library is available
 * @return bool
 */
function isGDAvailable() {
    return extension_loaded('gd') && function_exists('imagecreatefromjpeg');
}

/**
 * Compress and optimize image
 * @param string $source Source image path
 * @param string $destination Destination path (optional, overwrites source if not provided)
 * @param int $quality Quality (1-100, default 75)
 * @param int $maxWidth Maximum width (default 1920)
 * @param int $maxHeight Maximum height (default 1080)
 * @return bool Success status
 */
function compressImage($source, $destination = null, $quality = 75, $maxWidth = 1920, $maxHeight = 1080) {
    if (!isGDAvailable()) {
        return false;
    }
    
    if (!file_exists($source)) {
        return false;
    }
    
    // If no destination provided, overwrite source
    if ($destination === null) {
        $destination = $source;
    }
    
    // Get image info
    $imageInfo = getimagesize($source);
    if ($imageInfo === false) {
        return false;
    }
    
    list($width, $height, $type) = $imageInfo;
    
    // Create image resource based on type
    switch ($type) {
        case IMAGETYPE_JPEG:
            $image = imagecreatefromjpeg($source);
            break;
        case IMAGETYPE_PNG:
            $image = imagecreatefrompng($source);
            break;
        case IMAGETYPE_GIF:
            $image = imagecreatefromgif($source);
            break;
        case IMAGETYPE_WEBP:
            $image = imagecreatefromwebp($source);
            break;
        default:
            return false;
    }
    
    if ($image === false) {
        return false;
    }
    
    // Calculate new dimensions while maintaining aspect ratio
    $newWidth = $width;
    $newHeight = $height;
    
    if ($width > $maxWidth || $height > $maxHeight) {
        $ratio = min($maxWidth / $width, $maxHeight / $height);
        $newWidth = round($width * $ratio);
        $newHeight = round($height * $ratio);
    }
    
    // Create new image with new dimensions
    $newImage = imagecreatetruecolor($newWidth, $newHeight);
    
    // Preserve transparency for PNG and GIF
    if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF) {
        imagealphablending($newImage, false);
        imagesavealpha($newImage, true);
        $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
        imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
    }
    
    // Resize image
    imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    
    // Save compressed image
    $success = false;
    switch ($type) {
        case IMAGETYPE_JPEG:
            $success = imagejpeg($newImage, $destination, $quality);
            break;
        case IMAGETYPE_PNG:
            // PNG quality is 0-9 (0 = no compression, 9 = max compression)
            $pngQuality = round((100 - $quality) / 11);
            $success = imagepng($newImage, $destination, $pngQuality);
            break;
        case IMAGETYPE_GIF:
            $success = imagegif($newImage, $destination);
            break;
        case IMAGETYPE_WEBP:
            $success = imagewebp($newImage, $destination, $quality);
            break;
    }
    
    // Free memory
    imagedestroy($image);
    imagedestroy($newImage);
    
    return $success;
}

/**
 * Convert image to WebP format (best compression)
 * @param string $source Source image path
 * @param string $destination Destination WebP path
 * @param int $quality Quality (1-100, default 80)
 * @return bool Success status
 */
function convertToWebP($source, $destination = null, $quality = 80) {
    if (!file_exists($source)) {
        return false;
    }
    
    // If no destination provided, replace extension with .webp
    if ($destination === null) {
        $destination = preg_replace('/\.[^.]+$/', '.webp', $source);
    }
    
    // Get image info
    $imageInfo = getimagesize($source);
    if ($imageInfo === false) {
        return false;
    }
    
    list($width, $height, $type) = $imageInfo;
    
    // Create image resource
    switch ($type) {
        case IMAGETYPE_JPEG:
            $image = imagecreatefromjpeg($source);
            break;
        case IMAGETYPE_PNG:
            $image = imagecreatefrompng($source);
            break;
        case IMAGETYPE_GIF:
            $image = imagecreatefromgif($source);
            break;
        default:
            return false;
    }
    
    if ($image === false) {
        return false;
    }
    
    // Save as WebP
    $success = imagewebp($image, $destination, $quality);
    
    // Free memory
    imagedestroy($image);
    
    return $success;
}

/**
 * Get image file size in KB
 * @param string $path Image path
 * @return float Size in KB
 */
function getImageSizeKB($path) {
    if (!file_exists($path)) {
        return 0;
    }
    return filesize($path) / 1024;
}

/**
 * Compress image to target size
 * @param string $source Source image path
 * @param string $destination Destination path
 * @param int $targetSizeKB Target size in KB (default 300)
 * @return bool Success status
 */
function compressToTargetSize($source, $destination = null, $targetSizeKB = 300) {
    if (!isGDAvailable()) {
        return false;
    }
    
    if ($destination === null) {
        $destination = $source;
    }
    
    // Get original dimensions
    $imageInfo = getimagesize($source);
    if (!$imageInfo) {
        return false;
    }
    
    list($originalWidth, $originalHeight) = $imageInfo;
    
    // Start with high quality and gradually reduce
    $quality = 85;
    $minQuality = 40;
    $maxWidth = $originalWidth;
    $maxHeight = $originalHeight;
    
    // Try different quality levels
    while ($quality >= $minQuality) {
        compressImage($source, $destination, $quality, $maxWidth, $maxHeight);
        
        $sizeKB = getImageSizeKB($destination);
        
        if ($sizeKB <= $targetSizeKB) {
            return true;
        }
        
        $quality -= 10;
    }
    
    // If still too large, aggressively reduce dimensions
    $reductionSteps = [0.9, 0.8, 0.7, 0.6, 0.5, 0.4];
    
    foreach ($reductionSteps as $scale) {
        $maxWidth = round($originalWidth * $scale);
        $maxHeight = round($originalHeight * $scale);
        
        // Try with medium quality first
        compressImage($source, $destination, 70, $maxWidth, $maxHeight);
        $sizeKB = getImageSizeKB($destination);
        
        if ($sizeKB <= $targetSizeKB) {
            return true;
        }
        
        // Try with lower quality
        compressImage($source, $destination, 50, $maxWidth, $maxHeight);
        $sizeKB = getImageSizeKB($destination);
        
        if ($sizeKB <= $targetSizeKB) {
            return true;
        }
    }
    
    // Last resort: very aggressive compression
    $maxWidth = round($originalWidth * 0.3);
    $maxHeight = round($originalHeight * 0.3);
    compressImage($source, $destination, 40, $maxWidth, $maxHeight);
    
    return getImageSizeKB($destination) <= $targetSizeKB;
}

/**
 * Batch compress all images in a directory
 * @param string $directory Directory path
 * @param int $quality Quality (1-100)
 * @param int $targetSizeKB Target size in KB (0 = no target)
 * @return array Results with file paths and sizes
 */
function batchCompressImages($directory, $quality = 75, $targetSizeKB = 0) {
    $results = [];
    
    if (!is_dir($directory)) {
        return $results;
    }
    
    if (!isGDAvailable()) {
        return $results;
    }
    
    $files = glob($directory . '/*.{jpg,jpeg,png,gif,webp,JPG,JPEG,PNG,GIF,WEBP}', GLOB_BRACE);
    
    foreach ($files as $file) {
        $originalSize = getImageSizeKB($file);
        
        // Skip if file is already small enough
        if ($targetSizeKB > 0 && $originalSize <= $targetSizeKB) {
            $results[] = [
                'file' => basename($file),
                'original_size' => round($originalSize, 2),
                'new_size' => round($originalSize, 2),
                'saved' => 0,
                'saved_percent' => 0,
                'success' => true,
                'skipped' => true
            ];
            continue;
        }
        
        if ($targetSizeKB > 0) {
            $success = compressToTargetSize($file, null, $targetSizeKB);
        } else {
            $success = compressImage($file, null, $quality);
        }
        
        $newSize = getImageSizeKB($file);
        $saved = $originalSize - $newSize;
        $savedPercent = $originalSize > 0 ? round(($saved / $originalSize) * 100, 2) : 0;
        
        $results[] = [
            'file' => basename($file),
            'original_size' => round($originalSize, 2),
            'new_size' => round($newSize, 2),
            'saved' => round($saved, 2),
            'saved_percent' => $savedPercent,
            'success' => $success,
            'skipped' => false
        ];
    }
    
    return $results;
}

/**
 * Example usage:
 * 
 * // Compress single image
 * compressImage('uploads/image.jpg', null, 75);
 * 
 * // Compress to specific size
 * compressToTargetSize('uploads/image.jpg', null, 300);
 * 
 * // Convert to WebP
 * convertToWebP('uploads/image.jpg');
 * 
 * // Batch compress directory
 * $results = batchCompressImages('uploads/products', 75, 300);
 */
?>
