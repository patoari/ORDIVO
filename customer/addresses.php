<?php
/**
 * ORDIVO - Customer Addresses Page
 * Manage delivery addresses
 */

require_once '../config/db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: ../auth/login.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Handle address actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitizeInput($_POST['action'] ?? '');
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!validateCSRFToken($csrf_token)) {
        $error = 'Invalid security token. Please try again.';
    } else {
        try {
            if ($action === 'add') {
                $title = sanitizeInput($_POST['title'] ?? '');
                $address_line_1 = sanitizeInput($_POST['address_line_1'] ?? '');
                $address_line_2 = sanitizeInput($_POST['address_line_2'] ?? '');
                $city = sanitizeInput($_POST['city'] ?? '');
                $state = sanitizeInput($_POST['state'] ?? '');
                $postal_code = sanitizeInput($_POST['postal_code'] ?? '');
                $country = sanitizeInput($_POST['country'] ?? '');
                $phone = sanitizeInput($_POST['phone'] ?? '');
                $is_default = isset($_POST['is_default']) ? 1 : 0;
                
                if (empty($title) || empty($address_line_1) || empty($city)) {
                    $error = 'Title, address, and city are required.';
                } else {
                    // If this is set as default, remove default from other addresses
                    if ($is_default) {
                        updateData('user_addresses', ['is_default' => 0], 'user_id = ?', [$userId]);
                    }
                    
                    $addressData = [
                        'user_id' => $userId,
                        'title' => $title,
                        'address_line_1' => $address_line_1,
                        'address_line_2' => $address_line_2,
                        'city' => $city,
                        'state' => $state,
                        'postal_code' => $postal_code,
                        'country' => $country,
                        'phone' => $phone,
                        'is_default' => $is_default,
                        'created_at' => date('Y-m-d H:i:s')
                    ];
                    
                    $inserted = insertData('user_addresses', $addressData);
                    if ($inserted) {
                        $success = 'Address added successfully!';
                    } else {
                        $error = 'Failed to add address.';
                    }
                }
            } elseif ($action === 'delete') {
                $addressId = intval($_POST['address_id'] ?? 0);
                if ($addressId > 0) {
                    $deleted = deleteData('user_addresses', 'id = ? AND user_id = ?', [$addressId, $userId]);
                    if ($deleted) {
                        $success = 'Address deleted successfully!';
                    } else {
                        $error = 'Failed to delete address.';
                    }
                }
            } elseif ($action === 'set_default') {
                $addressId = intval($_POST['address_id'] ?? 0);
                if ($addressId > 0) {
                    // Remove default from all addresses
                    updateData('user_addresses', ['is_default' => 0], 'user_id = ?', [$userId]);
                    // Set new default
                    $updated = updateData('user_addresses', ['is_default' => 1], 'id = ? AND user_id = ?', [$addressId, $userId]);
                    if ($updated) {
                        $success = 'Default address updated!';
                    } else {
                        $error = 'Failed to update default address.';
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Address Action Error: " . $e->getMessage());
            $error = 'An error occurred while processing your request.';
        }
    }
}

try {
    // Get user's addresses
    $addresses = fetchAll("
        SELECT * FROM user_addresses 
        WHERE user_id = ? 
        ORDER BY is_default DESC, created_at DESC
    ", [$userId]);
    
} catch (Exception $e) {
    error_log("Addresses Fetch Error: " . $e->getMessage());
    $addresses = [];
}

// No sample data - only show real addresses from database
$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Addresses - ORDIVO</title>
    
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

        .page-header {
            background: #10b981; 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }

        .back-btn {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            color: #ffffff;
            text-decoration: none;
        }

        .address-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px #e5e7eb;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
            position: relative;
        }

        .address-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px #e5e7eb;
        }

        .address-card.default {
            border: 2px solid var(--ordivo-primary);
        }

        .default-badge {
            position: absolute;
            top: -10px;
            right: 20px;
            background: var(--ordivo-primary);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .address-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--ordivo-dark);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .address-details {
            color: #6c757d;
            line-height: 1.6;
            margin-bottom: 1rem;
        }

        .address-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.85rem;
        }

        .btn-primary {
            background: var(--ordivo-primary);
            border-color: var(--ordivo-primary);
        }

        .btn-primary:hover {
            background: var(--ordivo-secondary);
            border-color: var(--ordivo-secondary);
        }

        .btn-outline-primary {
            border-color: var(--ordivo-primary);
            color: var(--ordivo-primary);
        }

        .btn-outline-primary:hover {
            background: var(--ordivo-primary);
            border-color: var(--ordivo-primary);
        }

        .btn-outline-danger {
            border-color: #dc3545;
            color: #dc3545;
        }

        .btn-outline-danger:hover {
            background: #dc3545;
            border-color: #dc3545;
        }

        .add-address-card {
            background: var(--ordivo-light);
            border: 2px dashed var(--ordivo-primary);
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 2rem;
        }

        .add-address-card:hover {
            background: white;
            transform: translateY(-2px);
        }

        .add-icon {
            font-size: 3rem;
            color: var(--ordivo-primary);
            margin-bottom: 1rem;
        }

        .modal-content {
            border-radius: 15px;
            border: none;
        }

        .modal-header {
            background: var(--ordivo-light);
            border-radius: 15px 15px 0 0;
            border-bottom: 1px solid #e9ecef;
        }

        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--ordivo-primary);
            box-shadow: 0 0 0 0.2rem #f97316;
        }

        .alert {
            border-radius: 10px;
            border: none;
            margin-bottom: 2rem;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #6c757d;
        }

        .empty-icon {
            font-size: 4rem;
            color: #dee2e6;
            margin-bottom: 1rem;
        }

        @media (max-width: 768px) {
            .address-actions {
                flex-direction: column;
            }

            .address-card {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Page Header -->
    <div class="page-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-auto">
                    <a href="index.php" class="back-btn">
                        <i class="fas fa-arrow-left me-2"></i>Back to Home
                    </a>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col">
                    <h1 class="mb-1">
                        <i class="fas fa-map-marker-alt me-2"></i>My Addresses
                    </h1>
                    <p class="mb-0 opacity-75">Manage your delivery addresses</p>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <div class="alert alert-success" role="alert">
                <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <!-- Add New Address Card -->
        <div class="add-address-card" data-bs-toggle="modal" data-bs-target="#addAddressModal">
            <div class="add-icon">
                <i class="fas fa-plus-circle"></i>
            </div>
            <h4 class="text-primary mb-2">Add New Address</h4>
            <p class="text-muted mb-0">Click to add a new delivery address</p>
        </div>

        <!-- Addresses List -->
        <?php if (empty($addresses)): ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-map-marker-alt"></i>
                </div>
                <h3>No Addresses Added</h3>
                <p>Add your first delivery address to get started!</p>
            </div>
        <?php else: ?>
            <?php foreach ($addresses as $address): ?>
                <div class="address-card <?= $address['is_default'] ? 'default' : '' ?>">
                    <?php if ($address['is_default']): ?>
                        <div class="default-badge">
                            <i class="fas fa-star me-1"></i>Default
                        </div>
                    <?php endif; ?>
                    
                    <div class="address-title">
                        <i class="fas fa-<?= getAddressIcon($address['title']) ?>"></i>
                        <?= htmlspecialchars($address['title']) ?>
                    </div>
                    
                    <div class="address-details">
                        <div><?= htmlspecialchars($address['address_line_1']) ?></div>
                        <?php if (!empty($address['address_line_2'])): ?>
                            <div><?= htmlspecialchars($address['address_line_2']) ?></div>
                        <?php endif; ?>
                        <div><?= htmlspecialchars($address['city']) ?>, <?= htmlspecialchars($address['state']) ?> <?= htmlspecialchars($address['postal_code']) ?></div>
                        <?php if (!empty($address['country'])): ?>
                            <div><?= htmlspecialchars($address['country']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($address['phone'])): ?>
                            <div class="mt-2">
                                <i class="fas fa-phone me-1"></i><?= htmlspecialchars($address['phone']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="address-actions">
                        <?php if (!$address['is_default']): ?>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                <input type="hidden" name="action" value="set_default">
                                <input type="hidden" name="address_id" value="<?= $address['id'] ?>">
                                <button type="submit" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-star me-1"></i>Set as Default
                                </button>
                            </form>
                        <?php endif; ?>
                        
                        <button class="btn btn-outline-primary btn-sm" onclick="editAddress(<?= $address['id'] ?>)">
                            <i class="fas fa-edit me-1"></i>Edit
                        </button>
                        
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="address_id" value="<?= $address['id'] ?>">
                            <button type="submit" class="btn btn-outline-danger btn-sm" 
                                    onclick="return confirm('Are you sure you want to delete this address?')">
                                <i class="fas fa-trash me-1"></i>Delete
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Add Address Modal -->
    <div class="modal fade" id="addAddressModal" tabindex="-1" aria-labelledby="addAddressModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addAddressModalLabel">
                        <i class="fas fa-plus-circle me-2 text-primary"></i>Add New Address
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="title" class="form-label">Address Title *</label>
                                <input type="text" class="form-control" id="title" name="title" 
                                       placeholder="e.g., Home, Office, etc." required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       placeholder="+1 (555) 123-4567">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="address_line_1" class="form-label">Address Line 1 *</label>
                            <input type="text" class="form-control" id="address_line_1" name="address_line_1" 
                                   placeholder="Street address" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="address_line_2" class="form-label">Address Line 2</label>
                            <input type="text" class="form-control" id="address_line_2" name="address_line_2" 
                                   placeholder="Apartment, suite, etc. (optional)">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="city" class="form-label">City *</label>
                                <input type="text" class="form-control" id="city" name="city" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="state" class="form-label">State</label>
                                <input type="text" class="form-control" id="state" name="state">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="postal_code" class="form-label">Postal Code</label>
                                <input type="text" class="form-control" id="postal_code" name="postal_code">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="country" class="form-label">Country</label>
                                <input type="text" class="form-control" id="country" name="country" value="USA">
                            </div>
                            <div class="col-md-6 mb-3 d-flex align-items-end">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="is_default" name="is_default">
                                    <label class="form-check-label" for="is_default">
                                        Set as default address
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Save Address
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function editAddress(addressId) {
            // In a real app, this would populate the modal with existing data
            alert(`Edit address #${addressId}`);
        }

        // Auto-hide alerts after 5 seconds
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            }, 5000);
        });
    </script>
</body>
</html>

<?php
function getAddressIcon($title) {
    $icons = [
        'home' => 'home',
        'office' => 'building',
        'work' => 'building',
        'school' => 'school',
        'gym' => 'dumbbell',
        'hospital' => 'hospital',
        'hotel' => 'bed'
    ];
    
    $lowerTitle = strtolower($title);
    return $icons[$lowerTitle] ?? 'map-marker-alt';
}
?>