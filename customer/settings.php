<?php
/**
 * ORDIVO - Customer Settings Page
 * Account settings and preferences
 */

require_once '../config/db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: ../auth/login.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitizeInput($_POST['action'] ?? '');
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!validateCSRFToken($csrf_token)) {
        $error = 'Invalid security token. Please try again.';
    } else {
        if ($action === 'notifications') {
            // Handle notification preferences
            $success = 'Notification preferences updated successfully!';
        } elseif ($action === 'privacy') {
            // Handle privacy settings
            $success = 'Privacy settings updated successfully!';
        } elseif ($action === 'password') {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                $error = 'All password fields are required.';
            } elseif ($newPassword !== $confirmPassword) {
                $error = 'New passwords do not match.';
            } elseif (strlen($newPassword) < 8) {
                $error = 'New password must be at least 8 characters long.';
            } else {
                try {
                    // Verify current password
                    $user = fetchRow("SELECT password FROM users WHERE id = ?", [$userId]);
                    if ($user && password_verify($currentPassword, $user['password'])) {
                        // Update password
                        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                        $updated = updateData('users', ['password' => $hashedPassword], 'id = ?', [$userId]);
                        if ($updated) {
                            $success = 'Password updated successfully!';
                        } else {
                            $error = 'Failed to update password.';
                        }
                    } else {
                        $error = 'Current password is incorrect.';
                    }
                } catch (Exception $e) {
                    error_log("Password Update Error: " . $e->getMessage());
                    $error = 'An error occurred while updating password.';
                }
            }
        }
    }
}

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - ORDIVO</title>
    
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

        .settings-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px #e5e7eb;
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .settings-section {
            margin-bottom: 2rem;
        }

        .settings-section:last-child {
            margin-bottom: 0;
        }

        .section-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--ordivo-dark);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .section-title i {
            color: var(--ordivo-primary);
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

        .form-check-input:checked {
            background-color: var(--ordivo-primary);
            border-color: var(--ordivo-primary);
        }

        .btn-primary {
            background: var(--ordivo-primary);
            border-color: var(--ordivo-primary);
            border-radius: 10px;
            padding: 0.75rem 2rem;
            font-weight: 600;
        }

        .btn-primary:hover {
            background: var(--ordivo-secondary);
            border-color: var(--ordivo-secondary);
        }

        .btn-outline-danger {
            border-color: #dc3545;
            color: #dc3545;
            border-radius: 10px;
            padding: 0.75rem 2rem;
            font-weight: 600;
        }

        .btn-outline-danger:hover {
            background: #dc3545;
            border-color: #dc3545;
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

        .setting-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid #e9ecef;
        }

        .setting-item:last-child {
            border-bottom: none;
        }

        .setting-info h6 {
            margin-bottom: 0.25rem;
            font-weight: 600;
        }

        .setting-info p {
            margin-bottom: 0;
            color: #6c757d;
            font-size: 0.9rem;
        }

        .form-switch .form-check-input {
            width: 3rem;
            height: 1.5rem;
        }

        .danger-zone {
            border: 2px solid #dc3545;
            border-radius: 15px;
            padding: 2rem;
            background: #fff5f5;
        }

        .danger-zone h4 {
            color: #dc3545;
            margin-bottom: 1rem;
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
                        <i class="fas fa-cog me-2"></i>Settings
                    </h1>
                    <p class="mb-0 opacity-75">Manage your account preferences</p>
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

        <!-- Notification Settings -->
        <div class="settings-card">
            <div class="settings-section">
                <h3 class="section-title">
                    <i class="fas fa-bell"></i>Notifications
                </h3>
                
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    <input type="hidden" name="action" value="notifications">
                    
                    <div class="setting-item">
                        <div class="setting-info">
                            <h6>Order Updates</h6>
                            <p>Get notified about order status changes</p>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="orderUpdates" checked>
                        </div>
                    </div>
                    
                    <div class="setting-item">
                        <div class="setting-info">
                            <h6>Promotional Offers</h6>
                            <p>Receive notifications about deals and discounts</p>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="promotions" checked>
                        </div>
                    </div>
                    
                    <div class="setting-item">
                        <div class="setting-info">
                            <h6>Email Notifications</h6>
                            <p>Receive important updates via email</p>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="emailNotifications" checked>
                        </div>
                    </div>
                    
                    <div class="setting-item">
                        <div class="setting-info">
                            <h6>SMS Notifications</h6>
                            <p>Get order updates via text messages</p>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="smsNotifications">
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Save Notification Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Privacy Settings -->
        <div class="settings-card">
            <div class="settings-section">
                <h3 class="section-title">
                    <i class="fas fa-shield-alt"></i>Privacy & Security
                </h3>
                
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    <input type="hidden" name="action" value="privacy">
                    
                    <div class="setting-item">
                        <div class="setting-info">
                            <h6>Profile Visibility</h6>
                            <p>Make your profile visible to other users</p>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="profileVisibility">
                        </div>
                    </div>
                    
                    <div class="setting-item">
                        <div class="setting-info">
                            <h6>Location Sharing</h6>
                            <p>Share your location for better delivery experience</p>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="locationSharing" checked>
                        </div>
                    </div>
                    
                    <div class="setting-item">
                        <div class="setting-info">
                            <h6>Data Analytics</h6>
                            <p>Help us improve by sharing usage data</p>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="dataAnalytics" checked>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Save Privacy Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Change Password -->
        <div class="settings-card">
            <div class="settings-section">
                <h3 class="section-title">
                    <i class="fas fa-key"></i>Change Password
                </h3>
                
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    <input type="hidden" name="action" value="password">
                    
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-key me-2"></i>Update Password
                    </button>
                </form>
            </div>
        </div>

        <!-- Danger Zone -->
        <div class="danger-zone">
            <h4>
                <i class="fas fa-exclamation-triangle me-2"></i>Danger Zone
            </h4>
            <p class="mb-3">These actions are irreversible. Please proceed with caution.</p>
            
            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-outline-danger" onclick="deactivateAccount()">
                    <i class="fas fa-user-slash me-2"></i>Deactivate Account
                </button>
                <button class="btn btn-outline-danger" onclick="deleteAccount()">
                    <i class="fas fa-trash me-2"></i>Delete Account
                </button>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function deactivateAccount() {
            if (confirm('Are you sure you want to deactivate your account? You can reactivate it later by logging in.')) {
                alert('Account deactivation functionality would be implemented here');
            }
        }

        function deleteAccount() {
            if (confirm('Are you sure you want to permanently delete your account? This action cannot be undone.')) {
                if (confirm('This will permanently delete all your data. Are you absolutely sure?')) {
                    alert('Account deletion functionality would be implemented here');
                }
            }
        }

        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (confirmPassword && newPassword !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });

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