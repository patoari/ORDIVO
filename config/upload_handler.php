<?php
/**
 * ORDIVO Upload Handler
 * Handles file uploads with automatic compression
 */

require_once 'image_optimizer.php';

/**
 * Upload and compress image
 * @param array $file $_FILES array element
 * @param string $uploadDir Upload directory (relative to project root)
 * @param int $maxSizeKB Maximum file size in KB (default 300)
 * @param int $quality Image quality (default 75)
 * @return array Result with success status and file path
 */
function uploadAndCompressImage($file, $uploadDir, $maxSizeKB = 300, $quality = 75) {
    // Validate file
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return ['success' => false, 'message' => 'No file uploaded'];
    }
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Upload error: ' . $file['error']];
    }
    
    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        return ['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, GIF, and WebP are allowed.'];
    }
    
    // Create upload directory if it doesn't exist
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Generate unique filename
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $filepath = $uploadDir . '/' . $filename;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => false, 'message' => 'Failed to move uploaded file'];
    }
    
    // Get original file size
    $originalSizeKB = filesize($filepath) / 1024;
    
    // Compress image if it's larger than max size
    if ($originalSizeKB > $maxSizeKB) {
        $compressed = compressToTargetSize($filepath, null, $maxSizeKB);
        
        if (!$compressed) {
            // If compression failed, try with lower quality
            compressImage($filepath, null, $quality);
        }
    } else {
        // Even if under max size, optimize it
        compressImage($filepath, null, $quality);
    }
    
    $newSizeKB = filesize($filepath) / 1024;
    $savedKB = $originalSizeKB - $newSizeKB;
    
    return [
        'success' => true,
        'filepath' => $filepath,
        'filename' => $filename,
        'original_size' => round($originalSizeKB, 2),
        'compressed_size' => round($newSizeKB, 2),
        'saved' => round($savedKB, 2),
        'message' => 'Image uploaded and compressed successfully'
    ];
}

/**
 * Upload multiple images with compression
 * @param array $files $_FILES array
 * @param string $uploadDir Upload directory
 * @param int $maxSizeKB Maximum file size in KB
 * @param int $quality Image quality
 * @return array Results for each file
 */
function uploadMultipleImages($files, $uploadDir, $maxSizeKB = 300, $quality = 75) {
    $results = [];
    
    // Handle both single and multiple file uploads
    if (isset($files['tmp_name'])) {
        if (is_array($files['tmp_name'])) {
            // Multiple files
            $fileCount = count($files['tmp_name']);
            for ($i = 0; $i < $fileCount; $i++) {
                $file = [
                    'name' => $files['name'][$i],
                    'type' => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error' => $files['error'][$i],
                    'size' => $files['size'][$i]
                ];
                $results[] = uploadAndCompressImage($file, $uploadDir, $maxSizeKB, $quality);
            }
        } else {
            // Single file
            $results[] = uploadAndCompressImage($files, $uploadDir, $maxSizeKB, $quality);
        }
    }
    
    return $results;
}

/**
 * Delete uploaded file
 * @param string $filepath File path
 * @return bool Success status
 */
function deleteUploadedFile($filepath) {
    if (file_exists($filepath)) {
        return unlink($filepath);
    }
    return false;
}

/**
 * Example usage:
 * 
 * // Single file upload
 * $result = uploadAndCompressImage($_FILES['image'], '../uploads/products', 300, 75);
 * if ($result['success']) {
 *     echo "File uploaded: " . $result['filepath'];
 *     echo "Saved: " . $result['saved'] . " KB";
 * }
 * 
 * // Multiple files upload
 * $results = uploadMultipleImages($_FILES['images'], '../uploads/products', 300, 75);
 * foreach ($results as $result) {
 *     if ($result['success']) {
 *         echo "File uploaded: " . $result['filepath'];
 *     }
 * }
 */
?>
