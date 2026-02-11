<?php
/**
 * ORDIVO - Vendor Details & Business Management
 * Comprehensive vendor profile and business operations management
 */

require_once '../config/db_connection.php';

// Function to ensure required columns exist
function ensureVendorColumns() {
    global $pdo;
    try {
        $requiredColumns = [
            'onboarding_notes' => "ALTER TABLE vendors ADD COLUMN onboarding_notes TEXT DEFAULT NULL",
            'operating_hours' => "ALTER TABLE vendors ADD COLUMN operating_hours JSON DEFAULT NULL"
        ];
        
        foreach ($requiredColumns as $column => $sql) {
            $exists = fetchValue("SHOW COLUMNS FROM vendors LIKE '$column'");
            if (!$exists) {
                try {
                    $pdo->exec($sql);
                } catch (Exception $e) {
                    // Column might already exist or have different constraints
                }
            }
        }
        return true;
    } catch (Exception $e) {
        error_log("Error ensuring vendor columns: " . $e->getMessage());
        return false;
    }
}

// Ensure required columns exist
ensureVendorColumns();

// Check if user is logged in and is super admin
if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'super_admin') {
    // More robust redirect handling
    if (!headers_sent()) {
        header('Location: ../auth/login.php');
        exit;
    } else {
        // If headers already sent, use JavaScript redirect
        echo '<script>window.location.href="../auth/login.php";</script>';
        echo '<meta http-equiv="refresh" content="0;url=../auth/login.php">';
        echo '<p>Redirecting to login page... <a href="../auth/login.php">Click here if not redirected</a></p>';
        exit;
    }
}

$vendorId = (int)($_GET['id'] ?? 0);
if (!$vendorId) {
    header('Location: vendors.php');
    exit;
}

// Handle vendor updates
$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'update_vendor_profile':
            try {
                $userData = [
                    'name' => sanitizeInput($_POST['name'] ?? ''),
                    'email' => sanitizeInput($_POST['email'] ?? ''),
                    'phone' => sanitizeInput($_POST['phone'] ?? ''),
                    'status' => sanitizeInput($_POST['status'] ?? ''),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                updateData('users', $userData, 'id = ? AND role = ?', [$vendorId, 'vendor']);
                $success = 'Vendor profile updated successfully!';
            } catch (Exception $e) {
                $error = 'Failed to update vendor profile: ' . $e->getMessage();
            }
            break;
            
        case 'update_business_info':
            try {
                $businessData = [
                    'name' => sanitizeInput($_POST['business_name'] ?? ''),
                    'description' => sanitizeInput($_POST['description'] ?? ''),
                    'address' => sanitizeInput($_POST['address'] ?? ''),
                    'phone' => sanitizeInput($_POST['business_phone'] ?? ''),
                    'email' => sanitizeInput($_POST['business_email'] ?? ''),
                    'min_order_amount' => (float)($_POST['min_order_amount'] ?? 0),
                    'delivery_fee' => (float)($_POST['delivery_fee'] ?? 0),
                    'free_delivery_above' => (float)($_POST['free_delivery_above'] ?? 0),
                    'delivery_radius' => (float)($_POST['delivery_radius'] ?? 0),
                    'preparation_time' => (int)($_POST['preparation_time'] ?? 0),
                    'commission_rate' => (float)($_POST['commission_rate'] ?? 0),
                    'is_active' => isset($_POST['is_active']) ? 1 : 0,
                    'is_verified' => isset($_POST['is_verified']) ? 1 : 0,
                    'is_open' => isset($_POST['is_open']) ? 1 : 0,
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                updateData('vendors', $businessData, 'owner_id = ?', [$vendorId]);
                $success = 'Business information updated successfully!';
            } catch (Exception $e) {
                $error = 'Failed to update business information: ' . $e->getMessage();
            }
            break;
            
        case 'update_operating_hours':
            try {
                $operatingHours = [];
                $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                
                foreach ($days as $day) {
                    $isOpen = isset($_POST[$day . '_open']);
                    $openTime = sanitizeInput($_POST[$day . '_open_time'] ?? '09:00');
                    $closeTime = sanitizeInput($_POST[$day . '_close_time'] ?? '22:00');
                    
                    $operatingHours[$day] = [
                        'is_open' => $isOpen,
                        'open_time' => $openTime,
                        'close_time' => $closeTime
                    ];
                }
                
                updateData('vendors', [
                    'operating_hours' => json_encode($operatingHours),
                    'updated_at' => date('Y-m-d H:i:s')
                ], 'owner_id = ?', [$vendorId]);
                
                $success = 'Operating hours updated successfully!';
            } catch (Exception $e) {
                $error = 'Failed to update operating hours: ' . $e->getMessage();
            }
            break;
    }
}

// Get vendor details
try {
    $vendor = fetchRow("
        SELECT u.*, 
               COALESCE(v.name, u.name) as business_name,
               COALESCE(v.description, '') as description,
               COALESCE(v.address, '') as address,
               COALESCE(v.phone, u.phone) as business_phone,
               COALESCE(v.email, u.email) as business_email,
               COALESCE(v.min_order_amount, 100) as min_order_amount,
               COALESCE(v.delivery_fee, 50) as delivery_fee,
               COALESCE(v.free_delivery_above, 500) as free_delivery_above,
               COALESCE(v.delivery_radius, 10) as delivery_radius,
               COALESCE(v.preparation_time, 30) as preparation_time,
               COALESCE(v.commission_rate, 15) as commission_rate,
               COALESCE(v.is_active, 0) as is_active,
               COALESCE(v.is_verified, 0) as is_verified,
               COALESCE(v.is_open, 0) as is_open,
               COALESCE(v.operating_hours, '{}') as operating_hours
        FROM users u 
        LEFT JOIN vendors v ON u.id = v.owner_id 
        WHERE u.id = ? AND u.role = 'vendor'
    ", [$vendorId]);
    
    if (!$vendor) {
        header('Location: vendors.php');
        exit;
    }
    
    // Get vendor statistics
    $vendorStats = [
        'total_products' => fetchValue("SELECT COUNT(*) FROM products WHERE vendor_id = (SELECT id FROM vendors WHERE owner_id = ?)", [$vendorId]),
        'active_products' => fetchValue("SELECT COUNT(*) FROM products WHERE vendor_id = (SELECT id FROM vendors WHERE owner_id = ?) AND is_active = 1", [$vendorId]),
        'total_orders' => fetchValue("SELECT COUNT(*) FROM orders WHERE vendor_id = (SELECT id FROM vendors WHERE owner_id = ?)", [$vendorId]),
        'pending_orders' => fetchValue("SELECT COUNT(*) FROM orders WHERE vendor_id = (SELECT id FROM vendors WHERE owner_id = ?) AND status = 'pending'", [$vendorId]),
        'total_revenue' => fetchValue("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE vendor_id = (SELECT id FROM vendors WHERE owner_id = ?) AND payment_status = 'paid'", [$vendorId]),
        'avg_rating' => fetchValue("SELECT COALESCE(AVG(rating), 0) FROM reviews WHERE vendor_id = (SELECT id FROM vendors WHERE owner_id = ?)", [$vendorId])
    ];
    
    // Parse operating hours
    $operatingHours = [];
    if (!empty($vendor['operating_hours'])) {
        $operatingHours = json_decode($vendor['operating_hours'], true) ?: [];
    }
    
    // Default operating hours if not set
    $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
    foreach ($days as $day) {
        if (!isset($operatingHours[$day])) {
            $operatingHours[$day] = [
                'is_open' => true,
                'open_time' => '09:00',
                'close_time' => '22:00'
            ];
        }
    }
    
} catch (Exception $e) {
    $error = 'Failed to load vendor details: ' . $e->getMessage();
    $vendor = null;
    $vendorStats = [];
    $operatingHours = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Details - <?= htmlspecialchars($vendor['name'] ?? 'Unknown') ?> - ORDIVO Admin</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --ordivo-primary: #10b981;
            --ordivo-secondary: #059669;
            --ordivo-light: #f0fdf4;
            --ordivo-dark: #1a1a1a;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
        }

        .header {
            background: #10b981; 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }

        .btn-primary {
            background: #10b981; 100%);
            border: none;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 8px #e5e7eb;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 10px #e5e7eb;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px #e5e7eb;
            transition: all 0.3s ease;
            text-align: center;
            height: 100%;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px #e5e7eb;
        }

        .stat-value {
            font-size: 1.8rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #6c757d;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .vendor-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: var(--ordivo-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 2rem;
            margin: 0 auto 1rem;
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

        .form-control, .form-select {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 0.75rem;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--ordivo-primary);
            box-shadow: 0 2px 8px #e5e7eb;
        }

        .section-content {
            display: none;
        }

        .section-content.active {
            display: block;
        }

        .time-input {
            width: 120px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="mb-1">
                        <i class="fas fa-store me-3"></i><?= htmlspecialchars($vendor['name'] ?? 'Vendor Details') ?>
                    </h1>
                    <p class="mb-0 opacity-75">Comprehensive vendor management and business operations</p>
                </div>
                <a href="vendors.php" class="btn btn-light">
                    <i class="fas fa-arrow-left me-2"></i>Back to Vendors
                </a>
            </div>
        </div>
    </div>

    <div class="container">
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

        <?php if ($vendor): ?>
            <!-- Vendor Overview -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <div class="vendor-avatar">
                                <?= strtoupper(substr($vendor['name'], 0, 1)) ?>
                            </div>
                            <h5><?= htmlspecialchars($vendor['name']) ?></h5>
                            <p class="text-muted"><?= htmlspecialchars($vendor['email']) ?></p>
                            <?php
                            $statusColors = [
                                'pending' => 'warning',
                                'active' => 'success',
                                'inactive' => 'secondary',
                                'banned' => 'danger'
                            ];
                            $statusColor = $statusColors[$vendor['status']] ?? 'secondary';
                            $statusText = $vendor['status'] === 'banned' ? 'Suspended' : ucfirst($vendor['status']);
                            ?>
                            <span class="badge bg-<?= $statusColor ?> fs-6">
                                <?= $statusText ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-9">
                    <!-- Statistics -->
                    <div class="row">
                        <div class="col-md-2">
                            <div class="stat-card">
                                <div class="stat-value text-primary"><?= number_format($vendorStats['total_products'] ?? 0) ?></div>
                                <div class="stat-label">Products</div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="stat-card">
                                <div class="stat-value text-success"><?= number_format($vendorStats['active_products'] ?? 0) ?></div>
                                <div class="stat-label">Active</div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="stat-card">
                                <div class="stat-value text-info"><?= number_format($vendorStats['total_orders'] ?? 0) ?></div>
                                <div class="stat-label">Orders</div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="stat-card">
                                <div class="stat-value text-warning"><?= number_format($vendorStats['pending_orders'] ?? 0) ?></div>
                                <div class="stat-label">Pending</div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="stat-card">
                                <div class="stat-value text-success">৳<?= number_format($vendorStats['total_revenue'] ?? 0) ?></div>
                                <div class="stat-label">Revenue</div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="stat-card">
                                <div class="stat-value text-primary"><?= number_format($vendorStats['avg_rating'] ?? 0, 1) ?></div>
                                <div class="stat-label">Rating</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Navigation -->
                <div class="col-lg-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="nav flex-column nav-pills" role="tablist">
                                <button class="nav-link active" onclick="showSection('profile')">
                                    <i class="fas fa-user me-2"></i>Profile
                                </button>
                                <button class="nav-link" onclick="showSection('business')">
                                    <i class="fas fa-building me-2"></i>Business Info
                                </button>
                                <button class="nav-link" onclick="showSection('hours')">
                                    <i class="fas fa-clock me-2"></i>Operating Hours
                                </button>
                                <button class="nav-link" onclick="showSection('products')">
                                    <i class="fas fa-box me-2"></i>Products
                                </button>
                                <button class="nav-link" onclick="showSection('orders')">
                                    <i class="fas fa-shopping-cart me-2"></i>Orders
                                </button>
                                <button class="nav-link" onclick="showSection('analytics')">
                                    <i class="fas fa-chart-bar me-2"></i>Analytics
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Content -->
                <div class="col-lg-9">
                    <!-- Profile Section -->
                    <div id="profile-section" class="section-content active">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-user me-2"></i>Vendor Profile
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="update_vendor_profile">
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="name" class="form-label">Full Name</label>
                                            <input type="text" class="form-control" id="name" name="name" 
                                                   value="<?= htmlspecialchars($vendor['name'] ?? '') ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="email" class="form-label">Email Address</label>
                                            <input type="email" class="form-control" id="email" name="email" 
                                                   value="<?= htmlspecialchars($vendor['email'] ?? '') ?>" required>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="phone" class="form-label">Phone Number</label>
                                            <input type="tel" class="form-control" id="phone" name="phone" 
                                                   value="<?= htmlspecialchars($vendor['phone'] ?? '') ?>">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="status" class="form-label">Account Status</label>
                                            <select class="form-select" id="status" name="status">
                                                <option value="pending" <?= $vendor['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                                <option value="active" <?= $vendor['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                                <option value="inactive" <?= $vendor['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                                <option value="banned" <?= $vendor['status'] === 'banned' ? 'selected' : '' ?>>Suspended</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Registration Date</label>
                                            <input type="text" class="form-control" 
                                                   value="<?= date('F j, Y g:i A', strtotime($vendor['created_at'])) ?>" readonly>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Last Login</label>
                                            <input type="text" class="form-control" 
                                                   value="<?= $vendor['last_login_at'] ? date('F j, Y g:i A', strtotime($vendor['last_login_at'])) : 'Never' ?>" readonly>
                                        </div>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Update Profile
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Business Info Section -->
                    <div id="business-section" class="section-content">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-building me-2"></i>Business Information
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="update_business_info">
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="business_name" class="form-label">Business Name</label>
                                            <input type="text" class="form-control" id="business_name" name="business_name" 
                                                   value="<?= htmlspecialchars($vendor['name'] ?? '') ?>">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="business_phone" class="form-label">Business Phone</label>
                                            <input type="tel" class="form-control" id="business_phone" name="business_phone" 
                                                   value="<?= htmlspecialchars($vendor['phone'] ?? '') ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="description" class="form-label">Business Description</label>
                                        <textarea class="form-control" id="description" name="description" rows="3"><?= htmlspecialchars($vendor['description'] ?? '') ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="address" class="form-label">Business Address</label>
                                        <textarea class="form-control" id="address" name="address" rows="2"><?= htmlspecialchars($vendor['address'] ?? '') ?></textarea>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="business_email" class="form-label">Business Email</label>
                                            <input type="email" class="form-control" id="business_email" name="business_email" 
                                                   value="<?= htmlspecialchars($vendor['email'] ?? '') ?>">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="commission_rate" class="form-label">Commission Rate (%)</label>
                                            <input type="number" class="form-control" id="commission_rate" name="commission_rate" 
                                                   value="<?= $vendor['commission_rate'] ?? 15 ?>" min="0" max="50" step="0.1">
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-3 mb-3">
                                            <label for="min_order_amount" class="form-label">Min Order (৳)</label>
                                            <input type="number" class="form-control" id="min_order_amount" name="min_order_amount" 
                                                   value="<?= $vendor['min_order_amount'] ?? 100 ?>" min="0" step="0.01">
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label for="delivery_fee" class="form-label">Delivery Fee (৳)</label>
                                            <input type="number" class="form-control" id="delivery_fee" name="delivery_fee" 
                                                   value="<?= $vendor['delivery_fee'] ?? 50 ?>" min="0" step="0.01">
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label for="free_delivery_above" class="form-label">Free Delivery Above (৳)</label>
                                            <input type="number" class="form-control" id="free_delivery_above" name="free_delivery_above" 
                                                   value="<?= $vendor['free_delivery_above'] ?? 500 ?>" min="0" step="0.01">
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label for="delivery_radius" class="form-label">Delivery Radius (km)</label>
                                            <input type="number" class="form-control" id="delivery_radius" name="delivery_radius" 
                                                   value="<?= $vendor['delivery_radius'] ?? 10 ?>" min="0" step="0.1">
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="preparation_time" class="form-label">Preparation Time (minutes)</label>
                                            <input type="number" class="form-control" id="preparation_time" name="preparation_time" 
                                                   value="<?= $vendor['preparation_time'] ?? 30 ?>" min="0">
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                                       <?= ($vendor['is_active'] ?? 0) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="is_active">
                                                    Business Active
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="is_verified" name="is_verified" 
                                                       <?= ($vendor['is_verified'] ?? 0) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="is_verified">
                                                    Verified Business
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="is_open" name="is_open" 
                                                       <?= ($vendor['is_open'] ?? 0) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="is_open">
                                                    Currently Open
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Update Business Info
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Operating Hours Section -->
                    <div id="hours-section" class="section-content">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-clock me-2"></i>Operating Hours
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="update_operating_hours">
                                    
                                    <?php 
                                    $dayNames = [
                                        'monday' => 'Monday',
                                        'tuesday' => 'Tuesday', 
                                        'wednesday' => 'Wednesday',
                                        'thursday' => 'Thursday',
                                        'friday' => 'Friday',
                                        'saturday' => 'Saturday',
                                        'sunday' => 'Sunday'
                                    ];
                                    
                                    foreach ($dayNames as $day => $dayName): 
                                        $dayData = $operatingHours[$day] ?? ['is_open' => true, 'open_time' => '09:00', 'close_time' => '22:00'];
                                    ?>
                                        <div class="row align-items-center mb-3">
                                            <div class="col-md-2">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" 
                                                           id="<?= $day ?>_open" name="<?= $day ?>_open" 
                                                           <?= $dayData['is_open'] ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="<?= $day ?>_open">
                                                        <strong><?= $dayName ?></strong>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label small">Open Time</label>
                                                <input type="time" class="form-control time-input" 
                                                       name="<?= $day ?>_open_time" 
                                                       value="<?= $dayData['open_time'] ?>">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label small">Close Time</label>
                                                <input type="time" class="form-control time-input" 
                                                       name="<?= $day ?>_close_time" 
                                                       value="<?= $dayData['close_time'] ?>">
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                    
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Update Operating Hours
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Products Section -->
                    <div id="products-section" class="section-content">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="fas fa-box me-2"></i>Products Management
                                </h5>
                                <a href="products.php?vendor_id=<?= $vendorId ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus me-2"></i>Add Product
                                </a>
                            </div>
                            <div class="card-body">
                                <p class="text-muted">Product management will be loaded here...</p>
                                <a href="products.php?vendor_id=<?= $vendorId ?>" class="btn btn-outline-primary">
                                    <i class="fas fa-external-link-alt me-2"></i>Manage Products
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Orders Section -->
                    <div id="orders-section" class="section-content">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-shopping-cart me-2"></i>Orders Management
                                </h5>
                            </div>
                            <div class="card-body">
                                <p class="text-muted">Order management will be loaded here...</p>
                                <a href="orders.php?vendor_id=<?= $vendorId ?>" class="btn btn-outline-primary">
                                    <i class="fas fa-external-link-alt me-2"></i>Manage Orders
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Analytics Section -->
                    <div id="analytics-section" class="section-content">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-chart-bar me-2"></i>Vendor Analytics
                                </h5>
                            </div>
                            <div class="card-body">
                                <p class="text-muted">Analytics dashboard will be loaded here...</p>
                                <a href="analytics.php?vendor_id=<?= $vendorId ?>" class="btn btn-outline-primary">
                                    <i class="fas fa-external-link-alt me-2"></i>View Analytics
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                    <h4>Vendor Not Found</h4>
                    <p class="text-muted">The requested vendor could not be found.</p>
                    <a href="vendors.php" class="btn btn-primary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Vendors
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function showSection(sectionName) {
            // Hide all sections
            document.querySelectorAll('.section-content').forEach(section => {
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
        }
    </script>
</body>
</html>