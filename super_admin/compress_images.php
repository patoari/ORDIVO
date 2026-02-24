<?php
/**
 * ORDIVO - Image Compression Utility
 * Compress all existing images to reduce file sizes
 */

require_once '../config/db_connection.php';
require_once '../config/image_optimizer.php';

// Check if user is super admin
if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'super_admin') {
    header('Location: ../auth/login.php');
    exit;
}

$results = [];
$totalOriginalSize = 0;
$totalNewSize = 0;
$processedCount = 0;
$gdError = false;

// Check if GD library is available
if (!isGDAvailable()) {
    $gdError = true;
}

// Handle compression request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['compress'])) {
    if ($gdError) {
        $results = [['error' => 'GD library is not enabled']];
    } else {
    $targetSizeKB = intval($_POST['target_size'] ?? 300);
    $quality = intval($_POST['quality'] ?? 75);
    
    // Directories to compress
    $directories = [
        '../uploads/products',
        '../uploads/categories',
        '../uploads/vendors',
        '../uploads/banners',
        '../uploads/logos'
    ];
    
    foreach ($directories as $dir) {
        if (is_dir($dir)) {
            $dirResults = batchCompressImages($dir, $quality, $targetSizeKB);
            $results = array_merge($results, $dirResults);
            
            foreach ($dirResults as $result) {
                $totalOriginalSize += $result['original_size'];
                $totalNewSize += $result['new_size'];
                $processedCount++;
            }
        }
    }
}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Image Compression - ORDIVO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .btn-compress {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 10px;
            font-weight: 600;
        }
        
        .btn-compress:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            color: white;
        }
        
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .stats-card h3 {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .result-table {
            font-size: 0.9rem;
        }
        
        .badge-success {
            background: #28a745;
        }
        
        .badge-warning {
            background: #ffc107;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="container">
            <h1><i class="fas fa-compress-alt me-2"></i>Image Compression Utility</h1>
            <p class="mb-0">Optimize all images to improve website performance</p>
        </div>
    </div>
    
    <div class="container">
        <?php if ($gdError): ?>
        <!-- GD Library Error -->
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <h5><i class="fas fa-exclamation-triangle me-2"></i>GD Library Not Enabled</h5>
            <p class="mb-2">The GD library is required for image compression but is not currently enabled in your PHP installation.</p>
            <hr>
            <p class="mb-2"><strong>To enable GD library:</strong></p>
            <ol class="mb-2">
                <li>Open your <code>php.ini</code> file (usually located in <code>C:\xampp\php\php.ini</code>)</li>
                <li>Find the line: <code>;extension=gd</code></li>
                <li>Remove the semicolon (;) to uncomment it: <code>extension=gd</code></li>
                <li>Save the file and restart Apache from XAMPP Control Panel</li>
                <li>Refresh this page</li>
            </ol>
            <p class="mb-0"><strong>Alternative:</strong> You can also enable it from XAMPP Control Panel → Apache Config → php.ini</p>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <!-- Compression Form -->
        <div class="card">
            <div class="card-body p-4">
                <h4 class="mb-4"><i class="fas fa-cog me-2"></i>Compression Settings</h4>
                
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Target Size (KB)</label>
                            <input type="number" class="form-control" name="target_size" value="300" min="50" max="1000">
                            <small class="text-muted">Maximum file size in kilobytes</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Quality (%)</label>
                            <input type="number" class="form-control" name="quality" value="75" min="50" max="100">
                            <small class="text-muted">Image quality (50-100, higher = better quality)</small>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Note:</strong> This will compress all images in uploads/products, uploads/categories, uploads/vendors, uploads/banners, and uploads/logos directories.
                    </div>
                    
                    <button type="submit" name="compress" class="btn btn-compress">
                        <i class="fas fa-compress me-2"></i>Start Compression
                    </button>
                    <a href="dashboard.php" class="btn btn-outline-secondary ms-2">
                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                    </a>
                </form>
            </div>
        </div>
        
        <?php if (!empty($results)): ?>
        <!-- Results Summary -->
        <div class="row">
            <div class="col-md-4">
                <div class="stats-card">
                    <div class="text-center">
                        <i class="fas fa-images fa-2x mb-2"></i>
                        <h3><?= $processedCount ?></h3>
                        <p class="mb-0">Images Processed</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="stats-card">
                    <div class="text-center">
                        <i class="fas fa-database fa-2x mb-2"></i>
                        <h3><?= round($totalOriginalSize - $totalNewSize, 2) ?> KB</h3>
                        <p class="mb-0">Space Saved</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="stats-card">
                    <div class="text-center">
                        <i class="fas fa-percentage fa-2x mb-2"></i>
                        <h3><?= $totalOriginalSize > 0 ? round((($totalOriginalSize - $totalNewSize) / $totalOriginalSize) * 100, 1) : 0 ?>%</h3>
                        <p class="mb-0">Reduction</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Detailed Results -->
        <div class="card">
            <div class="card-body">
                <h4 class="mb-4"><i class="fas fa-list me-2"></i>Compression Results</h4>
                
                <div class="table-responsive">
                    <table class="table table-hover result-table">
                        <thead>
                            <tr>
                                <th>File Name</th>
                                <th>Original Size</th>
                                <th>New Size</th>
                                <th>Saved</th>
                                <th>Reduction</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results as $result): ?>
                            <tr class="<?= isset($result['skipped']) && $result['skipped'] ? 'table-secondary' : '' ?>">
                                <td>
                                    <i class="fas fa-image me-2 text-primary"></i><?= htmlspecialchars($result['file']) ?>
                                    <?php if (isset($result['skipped']) && $result['skipped']): ?>
                                        <span class="badge bg-info ms-2">Already Optimized</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= $result['original_size'] ?> KB</td>
                                <td>
                                    <?= $result['new_size'] ?> KB
                                    <?php if ($result['new_size'] > 300): ?>
                                        <span class="badge bg-warning text-dark ms-1">Still Large</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-success fw-bold"><?= $result['saved'] ?> KB</td>
                                <td>
                                    <span class="badge <?= $result['saved_percent'] > 30 ? 'badge-success' : ($result['saved_percent'] > 0 ? 'badge-warning' : 'bg-secondary') ?>">
                                        <?= $result['saved_percent'] ?>%
                                    </span>
                                </td>
                                <td>
                                    <?php if ($result['success']): ?>
                                        <i class="fas fa-check-circle text-success"></i> Success
                                    <?php else: ?>
                                        <i class="fas fa-times-circle text-danger"></i> Failed
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
