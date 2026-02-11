<?php
/**
 * ORDIVO - User Management System
 * Complete user management for all roles
 */

require_once '../config/db_connection.php';

// Check if user is logged in and is super admin
if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'super_admin') {
    header('Location: ../auth/login.php');
    exit;
}

// Handle user actions
$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $userId = (int)($_POST['user_id'] ?? 0);
    
    switch ($action) {
        case 'ban_user':
            if ($userId) {
                try {
                    updateData('users', ['status' => 'banned'], 'id = ? AND role != ?', [$userId, 'super_admin']);
                    $success = 'User banned successfully!';
                } catch (Exception $e) {
                    $error = 'Failed to ban user: ' . $e->getMessage();
                }
            }
            break;
            
        case 'unban_user':
            if ($userId) {
                try {
                    updateData('users', ['status' => 'active'], 'id = ?', [$userId]);
                    $success = 'User unbanned successfully!';
                } catch (Exception $e) {
                    $error = 'Failed to unban user: ' . $e->getMessage();
                }
            }
            break;
            
        case 'delete_user':
            if ($userId) {
                try {
                    deleteData('users', 'id = ? AND role != ?', [$userId, 'super_admin']);
                    $success = 'User deleted successfully!';
                } catch (Exception $e) {
                    $error = 'Failed to delete user: ' . $e->getMessage();
                }
            }
            break;
            
        case 'change_role':
            $newRole = sanitizeInput($_POST['new_role'] ?? '');
            if ($userId && $newRole) {
                try {
                    updateData('users', ['role' => $newRole], 'id = ? AND role != ?', [$userId, 'super_admin']);
                    $success = 'User role updated successfully!';
                } catch (Exception $e) {
                    $error = 'Failed to update user role: ' . $e->getMessage();
                }
            }
            break;
    }
}

// Get users with pagination
$page = (int)($_GET['page'] ?? 1);
$limit = 20;
$offset = ($page - 1) * $limit;

$search = sanitizeInput($_GET['search'] ?? '');
$roleFilter = sanitizeInput($_GET['role'] ?? '');
$statusFilter = sanitizeInput($_GET['status'] ?? '');

// Build query
$whereConditions = [];
$params = [];

if ($search) {
    $whereConditions[] = "(name LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($roleFilter) {
    $whereConditions[] = "role = ?";
    $params[] = $roleFilter;
}

if ($statusFilter) {
    $whereConditions[] = "status = ?";
    $params[] = $statusFilter;
}

$whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

try {
    $users = fetchAll("
        SELECT id, name, email, phone, role, status, created_at, last_login_at
        FROM users 
        $whereClause
        ORDER BY created_at DESC 
        LIMIT $limit OFFSET $offset
    ", $params);
    
    $totalUsers = fetchValue("SELECT COUNT(*) FROM users $whereClause", $params);
    $totalPages = ceil($totalUsers / $limit);
} catch (Exception $e) {
    $users = [];
    $totalUsers = 0;
    $totalPages = 1;
    $error = 'Failed to load users: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - ORDIVO Admin</title>
    
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
            box-shadow: 0 4px 15px #f97316;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 10px #e5e7eb;
        }

        .table th {
            background: #f8f9fa;
            font-weight: 600;
            border-top: none;
        }

        .badge {
            font-size: 0.75rem;
            padding: 0.35rem 0.65rem;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--ordivo-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
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
                        <i class="fas fa-users me-3"></i>User Management
                    </h1>
                    <p class="mb-0 opacity-75">Manage all platform users and their permissions</p>
                </div>
                <a href="dashboard.php" class="btn btn-light">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
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

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label for="search" class="form-label">Search Users</label>
                        <input type="text" class="form-control" id="search" name="search" 
                               placeholder="Name or email..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="role" class="form-label">Role</label>
                        <select class="form-select" id="role" name="role">
                            <option value="">All Roles</option>
                            <option value="customer" <?= $roleFilter === 'customer' ? 'selected' : '' ?>>Customer</option>
                            <option value="vendor" <?= $roleFilter === 'vendor' ? 'selected' : '' ?>>Vendor</option>
                            <option value="delivery_rider" <?= $roleFilter === 'delivery_rider' ? 'selected' : '' ?>>Delivery Rider</option>
                            <option value="kitchen_staff" <?= $roleFilter === 'kitchen_staff' ? 'selected' : '' ?>>Kitchen Staff</option>
                            <option value="store_staff" <?= $roleFilter === 'store_staff' ? 'selected' : '' ?>>Store Staff</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">All Status</option>
                            <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                            <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="banned" <?= $statusFilter === 'banned' ? 'selected' : '' ?>>Banned</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-2"></i>Filter
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Users Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-users me-2"></i>Users (<?= number_format($totalUsers) ?>)
                </h5>
                <div>
                    <button class="btn btn-outline-primary btn-sm" onclick="location.reload()">
                        <i class="fas fa-sync-alt me-2"></i>Refresh
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Contact</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Joined</th>
                                <th>Last Login</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        <i class="fas fa-users fa-2x mb-3"></i><br>
                                        No users found
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="user-avatar me-3">
                                                    <?= strtoupper(substr($user['name'], 0, 1)) ?>
                                                </div>
                                                <div>
                                                    <div class="fw-bold"><?= htmlspecialchars($user['name']) ?></div>
                                                    <small class="text-muted">ID: <?= $user['id'] ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div><?= htmlspecialchars($user['email']) ?></div>
                                            <?php if ($user['phone']): ?>
                                                <small class="text-muted"><?= htmlspecialchars($user['phone']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $roleColors = [
                                                'customer' => 'primary',
                                                'vendor' => 'info',
                                                'delivery_rider' => 'warning',
                                                'kitchen_staff' => 'success',
                                                'store_staff' => 'secondary',
                                                'super_admin' => 'dark'
                                            ];
                                            $roleColor = $roleColors[$user['role']] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?= $roleColor ?>">
                                                <?= ucwords(str_replace('_', ' ', $user['role'])) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            $statusColors = [
                                                'active' => 'success',
                                                'inactive' => 'secondary',
                                                'pending' => 'warning',
                                                'banned' => 'danger'
                                            ];
                                            $statusColor = $statusColors[$user['status']] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?= $statusColor ?>">
                                                <?= ucfirst($user['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <small><?= date('M j, Y', strtotime($user['created_at'])) ?></small>
                                        </td>
                                        <td>
                                            <?php if ($user['last_login_at']): ?>
                                                <small><?= date('M j, Y', strtotime($user['last_login_at'])) ?></small>
                                            <?php else: ?>
                                                <small class="text-muted">Never</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($user['role'] !== 'super_admin'): ?>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-primary" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#editUserModal"
                                                            onclick="editUser(<?= $user['id'] ?>, '<?= htmlspecialchars($user['name']) ?>', '<?= $user['role'] ?>', '<?= $user['status'] ?>')">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    
                                                    <?php if ($user['status'] === 'banned'): ?>
                                                        <button class="btn btn-outline-success" 
                                                                onclick="unbanUser(<?= $user['id'] ?>)">
                                                            <i class="fas fa-unlock"></i>
                                                        </button>
                                                    <?php else: ?>
                                                        <button class="btn btn-outline-warning" 
                                                                onclick="banUser(<?= $user['id'] ?>)">
                                                            <i class="fas fa-ban"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    
                                                    <button class="btn btn-outline-danger" 
                                                            onclick="deleteUser(<?= $user['id'] ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            <?php else: ?>
                                                <span class="badge bg-dark">Protected</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="card-footer">
                    <nav aria-label="Users pagination">
                        <ul class="pagination justify-content-center mb-0">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&role=<?= urlencode($roleFilter) ?>&status=<?= urlencode($statusFilter) ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editUserForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="change_role">
                        <input type="hidden" name="user_id" id="editUserId">
                        
                        <div class="mb-3">
                            <label class="form-label">User Name</label>
                            <input type="text" class="form-control" id="editUserName" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editUserRole" class="form-label">Role</label>
                            <select class="form-select" id="editUserRole" name="new_role" required>
                                <option value="customer">Customer</option>
                                <option value="vendor">Vendor</option>
                                <option value="delivery_rider">Delivery Rider</option>
                                <option value="kitchen_staff">Kitchen Staff</option>
                                <option value="store_staff">Store Staff</option>
                                <option value="kitchen_manager">Kitchen Manager</option>
                                <option value="store_manager">Store Manager</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function editUser(id, name, role, status) {
            document.getElementById('editUserId').value = id;
            document.getElementById('editUserName').value = name;
            document.getElementById('editUserRole').value = role;
        }

        function banUser(userId) {
            if (confirm('Are you sure you want to ban this user?')) {
                submitAction('ban_user', userId);
            }
        }

        function unbanUser(userId) {
            if (confirm('Are you sure you want to unban this user?')) {
                submitAction('unban_user', userId);
            }
        }

        function deleteUser(userId) {
            if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
                submitAction('delete_user', userId);
            }
        }

        function submitAction(action, userId) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="${action}">
                <input type="hidden" name="user_id" value="${userId}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    </script>
</body>
</html>