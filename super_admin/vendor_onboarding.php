<?php
/**
 * ORDIVO - Vendor Onboarding System
 * Complete vendor application and approval workflow
 */

require_once '../config/db_connection.php';

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

// Function to ensure required columns exist
function ensureVendorColumns() {
    global $pdo;
    try {
        // Check if onboarding_notes column exists
        $columnExists = fetchValue("SHOW COLUMNS FROM vendors LIKE 'onboarding_notes'");
        if (!$columnExists) {
            $pdo->exec("ALTER TABLE vendors ADD COLUMN onboarding_notes TEXT DEFAULT NULL");
        }
        
        // Check if other commonly needed columns exist
        $requiredColumns = [
            'description' => "ALTER TABLE vendors ADD COLUMN description TEXT DEFAULT NULL",
            'address' => "ALTER TABLE vendors ADD COLUMN address TEXT DEFAULT NULL",
            'phone' => "ALTER TABLE vendors ADD COLUMN phone VARCHAR(20) DEFAULT NULL",
            'email' => "ALTER TABLE vendors ADD COLUMN email VARCHAR(100) DEFAULT NULL"
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

// Handle onboarding actions
$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $vendorId = (int)($_POST['vendor_id'] ?? 0);
    
    switch ($action) {
        case 'complete_onboarding':
            if ($vendorId) {
                try {
                    // Update vendor status to active
                    updateData('users', [
                        'status' => 'active',
                        'updated_at' => date('Y-m-d H:i:s')
                    ], 'id = ? AND role = ?', [$vendorId, 'vendor']);
                    
                    // Update vendor business status
                    updateData('vendors', [
                        'is_active' => 1,
                        'is_verified' => 1,
                        'updated_at' => date('Y-m-d H:i:s')
                    ], 'owner_id = ?', [$vendorId]);
                    
                    $success = 'Vendor onboarding completed successfully!';
                } catch (Exception $e) {
                    $error = 'Failed to complete onboarding: ' . $e->getMessage();
                }
            }
            break;
            
        case 'send_requirements':
            if ($vendorId) {
                try {
                    $requirements = sanitizeInput($_POST['requirements'] ?? '');
                    
                    // Here you would typically send an email to the vendor
                    // For now, we'll just update a requirements field
                    updateData('vendors', [
                        'onboarding_notes' => $requirements,
                        'updated_at' => date('Y-m-d H:i:s')
                    ], 'owner_id = ?', [$vendorId]);
                    
                    $success = 'Requirements sent to vendor successfully!';
                } catch (Exception $e) {
                    $error = 'Failed to send requirements: ' . $e->getMessage();
                }
            }
            break;
    }
}

// Get pending vendors for onboarding
try {
    $pendingVendors = fetchAll("
        SELECT u.id, u.name, u.email, u.phone, u.created_at,
               COALESCE(v.name, '') as business_name, 
               COALESCE(v.description, '') as description, 
               COALESCE(v.address, '') as address, 
               COALESCE(v.onboarding_notes, '') as onboarding_notes
        FROM users u 
        LEFT JOIN vendors v ON u.id = v.owner_id 
        WHERE u.role = 'vendor' AND u.status = 'pending'
        ORDER BY u.created_at ASC
    ");
    
    // Get onboarding statistics
    $onboardingStats = [
        'pending_applications' => fetchValue("SELECT COUNT(*) FROM users WHERE role = 'vendor' AND status = 'pending'"),
        'incomplete_profiles' => fetchValue("
            SELECT COUNT(*) 
            FROM users u 
            LEFT JOIN vendors v ON u.id = v.owner_id 
            WHERE u.role = 'vendor' AND u.status = 'pending' 
            AND (v.description IS NULL OR v.description = '' OR v.description IS NULL)
        "),
        'awaiting_documents' => fetchValue("
            SELECT COUNT(*) 
            FROM users u 
            LEFT JOIN vendors v ON u.id = v.owner_id 
            WHERE u.role = 'vendor' AND u.status = 'pending' 
            AND v.onboarding_notes IS NOT NULL AND v.onboarding_notes != ''
        "),
        'ready_for_approval' => fetchValue("
            SELECT COUNT(*) 
            FROM users u 
            LEFT JOIN vendors v ON u.id = v.owner_id 
            WHERE u.role = 'vendor' AND u.status = 'pending' 
            AND v.description IS NOT NULL AND v.description != ''
            AND v.address IS NOT NULL AND v.address != ''
        ")
    ];
    
} catch (Exception $e) {
    $pendingVendors = [];
    $onboardingStats = ['pending_applications' => 0, 'incomplete_profiles' => 0, 'awaiting_documents' => 0, 'ready_for_approval' => 0];
    $error = 'Failed to load onboarding data: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Onboarding - ORDIVO Admin</title>
    
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
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px #e5e7eb;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .onboarding-card {
            border-left: 4px solid var(--ordivo-primary);
            transition: all 0.3s ease;
        }

        .onboarding-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px #e5e7eb;
        }

        .progress-indicator {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }

        .progress-step {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .progress-step.completed {
            background: var(--ordivo-primary);
            color: white;
        }

        .progress-step.current {
            background: #ffc107;
            color: #000;
        }

        .vendor-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--ordivo-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1.2rem;
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
                        <i class="fas fa-user-plus me-3"></i>Vendor Onboarding
                    </h1>
                    <p class="mb-0 opacity-75">Guide new vendors through the application process</p>
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

        <!-- Onboarding Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-value text-warning"><?= number_format($onboardingStats['pending_applications']) ?></div>
                    <div class="stat-label">Pending Applications</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-value text-danger"><?= number_format($onboardingStats['incomplete_profiles']) ?></div>
                    <div class="stat-label">Incomplete Profiles</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-value text-info"><?= number_format($onboardingStats['awaiting_documents']) ?></div>
                    <div class="stat-label">Awaiting Documents</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-value text-success"><?= number_format($onboardingStats['ready_for_approval']) ?></div>
                    <div class="stat-label">Ready for Approval</div>
                </div>
            </div>
        </div>

        <!-- Onboarding Process Overview -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-route me-2"></i>Onboarding Process
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-2 text-center">
                        <div class="progress-step completed">1</div>
                        <h6>Application</h6>
                        <small class="text-muted">Vendor submits initial application</small>
                    </div>
                    <div class="col-md-2 text-center">
                        <div class="progress-step current">2</div>
                        <h6>Profile Review</h6>
                        <small class="text-muted">Admin reviews vendor information</small>
                    </div>
                    <div class="col-md-2 text-center">
                        <div class="progress-step">3</div>
                        <h6>Documentation</h6>
                        <small class="text-muted">Collect required documents</small>
                    </div>
                    <div class="col-md-2 text-center">
                        <div class="progress-step">4</div>
                        <h6>Verification</h6>
                        <small class="text-muted">Verify business details</small>
                    </div>
                    <div class="col-md-2 text-center">
                        <div class="progress-step">5</div>
                        <h6>Setup</h6>
                        <small class="text-muted">Configure business settings</small>
                    </div>
                    <div class="col-md-2 text-center">
                        <div class="progress-step">6</div>
                        <h6>Approval</h6>
                        <small class="text-muted">Final approval and activation</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Applications -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-clock me-2"></i>Pending Applications
                </h5>
                <button class="btn btn-outline-primary btn-sm" onclick="location.reload()">
                    <i class="fas fa-sync-alt me-2"></i>Refresh
                </button>
            </div>
            <div class="card-body">
                <?php if (empty($pendingVendors)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                        <h4>All Caught Up!</h4>
                        <p class="text-muted">No pending vendor applications at the moment.</p>
                        <a href="vendors.php" class="btn btn-primary">
                            <i class="fas fa-store me-2"></i>View All Vendors
                        </a>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($pendingVendors as $vendor): ?>
                            <div class="col-lg-6 mb-4">
                                <div class="card onboarding-card">
                                    <div class="card-body">
                                        <div class="d-flex align-items-start mb-3">
                                            <div class="vendor-avatar me-3">
                                                <?= strtoupper(substr($vendor['name'], 0, 1)) ?>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1"><?= htmlspecialchars($vendor['name']) ?></h6>
                                                <p class="text-muted mb-1"><?= htmlspecialchars($vendor['email']) ?></p>
                                                <small class="text-muted">Applied: <?= date('M j, Y', strtotime($vendor['created_at'])) ?></small>
                                            </div>
                                        </div>
                                        
                                        <?php if ($vendor['business_name']): ?>
                                            <div class="mb-2">
                                                <strong>Business:</strong> <?= htmlspecialchars($vendor['business_name']) ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($vendor['description']): ?>
                                            <div class="mb-2">
                                                <strong>Description:</strong> 
                                                <small><?= htmlspecialchars(substr($vendor['description'], 0, 100)) ?><?= strlen($vendor['description']) > 100 ? '...' : '' ?></small>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($vendor['address']): ?>
                                            <div class="mb-3">
                                                <strong>Address:</strong> 
                                                <small><?= htmlspecialchars($vendor['address']) ?></small>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <!-- Onboarding Status -->
                                        <div class="mb-3">
                                            <div class="progress-indicator">
                                                <?php
                                                $completionScore = 0;
                                                if ($vendor['business_name']) $completionScore += 25;
                                                if ($vendor['description']) $completionScore += 25;
                                                if ($vendor['address']) $completionScore += 25;
                                                if ($vendor['phone']) $completionScore += 25;
                                                ?>
                                                <div class="progress flex-grow-1 me-2" style="height: 8px;">
                                                    <div class="progress-bar bg-primary" style="width: <?= $completionScore ?>%"></div>
                                                </div>
                                                <small class="text-muted"><?= $completionScore ?>% Complete</small>
                                            </div>
                                        </div>
                                        
                                        <?php if ($vendor['onboarding_notes']): ?>
                                            <div class="alert alert-info alert-sm mb-3">
                                                <strong>Notes:</strong> <?= htmlspecialchars($vendor['onboarding_notes']) ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <!-- Actions -->
                                        <div class="d-flex gap-2">
                                            <a href="vendor_details.php?id=<?= $vendor['id'] ?>" class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-eye me-1"></i>Review
                                            </a>
                                            
                                            <?php if ($completionScore >= 75): ?>
                                                <button class="btn btn-success btn-sm" onclick="completeOnboarding(<?= $vendor['id'] ?>)">
                                                    <i class="fas fa-check me-1"></i>Approve
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn-warning btn-sm" onclick="sendRequirements(<?= $vendor['id'] ?>)">
                                                    <i class="fas fa-paper-plane me-1"></i>Send Requirements
                                                </button>
                                            <?php endif; ?>
                                            
                                            <button class="btn btn-outline-danger btn-sm" onclick="rejectApplication(<?= $vendor['id'] ?>)">
                                                <i class="fas fa-times me-1"></i>Reject
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Requirements Modal -->
    <div class="modal fade" id="requirementsModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Send Requirements</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="send_requirements">
                        <input type="hidden" name="vendor_id" id="requirements_vendor_id">
                        
                        <div class="mb-3">
                            <label for="requirements" class="form-label">Requirements & Instructions</label>
                            <textarea class="form-control" id="requirements" name="requirements" rows="5" 
                                      placeholder="Please provide the following documents and information..."></textarea>
                        </div>
                        
                        <div class="alert alert-info">
                            <strong>Common Requirements:</strong>
                            <ul class="mb-0 mt-2">
                                <li>Business registration certificate</li>
                                <li>Tax identification number</li>
                                <li>Food safety license (for restaurants)</li>
                                <li>Bank account details</li>
                                <li>Identity verification documents</li>
                            </ul>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-2"></i>Send Requirements
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function completeOnboarding(vendorId) {
            if (confirm('Are you sure you want to approve this vendor and complete their onboarding?')) {
                submitAction('complete_onboarding', vendorId);
            }
        }

        function sendRequirements(vendorId) {
            document.getElementById('requirements_vendor_id').value = vendorId;
            new bootstrap.Modal(document.getElementById('requirementsModal')).show();
        }

        function rejectApplication(vendorId) {
            if (confirm('Are you sure you want to reject this vendor application? This action cannot be undone.')) {
                submitAction('reject_vendor', vendorId);
            }
        }

        function submitAction(action, vendorId) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="${action}">
                <input type="hidden" name="vendor_id" value="${vendorId}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    </script>
</body>
</html>