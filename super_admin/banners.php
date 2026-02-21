<?php
require_once '../config/db_connection.php';

// Check if user is super admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: ../auth/login.php');
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $title = sanitizeInput($_POST['title']);
                $subtitle = sanitizeInput($_POST['subtitle']);
                $banner_type = sanitizeInput($_POST['banner_type']);
                $position = sanitizeInput($_POST['position']);
                $background_color = sanitizeInput($_POST['background_color']);
                $text_color = sanitizeInput($_POST['text_color']);
                $button_text = sanitizeInput($_POST['button_text']);
                $button_link = sanitizeInput($_POST['button_link']);
                $display_order = (int)$_POST['display_order'];
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                
                // Handle image upload
                $background_image = null;
                if (isset($_FILES['background_image']) && $_FILES['background_image']['error'] === 0) {
                    $upload_dir = '../uploads/banners/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    $file_extension = pathinfo($_FILES['background_image']['name'], PATHINFO_EXTENSION);
                    $file_name = 'banner_' . time() . '_' . rand(1000, 9999) . '.' . $file_extension;
                    $target_file = $upload_dir . $file_name;
                    
                    if (move_uploaded_file($_FILES['background_image']['tmp_name'], $target_file)) {
                        $background_image = 'uploads/banners/' . $file_name;
                    }
                }
                
                $sql = "INSERT INTO site_banners (title, subtitle, banner_type, position, background_image, 
                        background_color, text_color, button_text, button_link, display_order, is_active, created_by) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                executeQuery($sql, [$title, $subtitle, $banner_type, $position, $background_image, 
                            $background_color, $text_color, $button_text, $button_link, $display_order, $is_active, $_SESSION['user_id']]);
                
                $_SESSION['success_message'] = 'Banner added successfully!';
                break;
                
            case 'edit':
                $id = (int)$_POST['id'];
                $title = sanitizeInput($_POST['title']);
                $subtitle = sanitizeInput($_POST['subtitle']);
                $banner_type = sanitizeInput($_POST['banner_type']);
                $position = sanitizeInput($_POST['position']);
                $background_color = sanitizeInput($_POST['background_color']);
                $text_color = sanitizeInput($_POST['text_color']);
                $button_text = sanitizeInput($_POST['button_text']);
                $button_link = sanitizeInput($_POST['button_link']);
                $display_order = (int)$_POST['display_order'];
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                
                // Handle image upload
                $background_image = $_POST['existing_image'];
                if (isset($_FILES['background_image']) && $_FILES['background_image']['error'] === 0) {
                    $upload_dir = '../uploads/banners/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    $file_extension = pathinfo($_FILES['background_image']['name'], PATHINFO_EXTENSION);
                    $file_name = 'banner_' . time() . '_' . rand(1000, 9999) . '.' . $file_extension;
                    $target_file = $upload_dir . $file_name;
                    
                    if (move_uploaded_file($_FILES['background_image']['tmp_name'], $target_file)) {
                        // Delete old image
                        if (!empty($background_image) && file_exists('../' . $background_image)) {
                            unlink('../' . $background_image);
                        }
                        $background_image = 'uploads/banners/' . $file_name;
                    }
                }
                
                $sql = "UPDATE site_banners SET title=?, subtitle=?, banner_type=?, position=?, 
                        background_image=?, background_color=?, text_color=?, button_text=?, 
                        button_link=?, display_order=?, is_active=? WHERE id=?";
                executeQuery($sql, [$title, $subtitle, $banner_type, $position, $background_image, 
                            $background_color, $text_color, $button_text, $button_link, $display_order, $is_active, $id]);
                
                $_SESSION['success_message'] = 'Banner updated successfully!';
                break;
                
            case 'delete':
                $id = (int)$_POST['id'];
                $banner = fetchRow("SELECT background_image FROM site_banners WHERE id = ?", [$id]);
                if ($banner && !empty($banner['background_image']) && file_exists('../' . $banner['background_image'])) {
                    unlink('../' . $banner['background_image']);
                }
                executeQuery("DELETE FROM site_banners WHERE id = ?", [$id]);
                $_SESSION['success_message'] = 'Banner deleted successfully!';
                break;
                
            case 'toggle_status':
                $id = (int)$_POST['id'];
                executeQuery("UPDATE site_banners SET is_active = NOT is_active WHERE id = ?", [$id]);
                $_SESSION['success_message'] = 'Banner status updated!';
                break;
        }
        header('Location: banners.php');
        exit;
    }
}

// Get all banners
$banners = fetchAll("SELECT * FROM site_banners ORDER BY display_order ASC, created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Banner Management - ORDIVO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #f97316;
            --secondary-color: #10b981;
        }
        
        body {
            background: #f8f9fa;
        }
        
        .banner-preview {
            border-radius: 12px;
            padding: 2rem;
            color: white;
            min-height: 150px;
            display: flex;
            align-items: center;
            background-size: cover;
            background-position: center;
            position: relative;
        }
        
        .banner-preview::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: inherit;
            border-radius: 12px;
        }
        
        .banner-content {
            position: relative;
            z-index: 1;
        }
        
        .btn-primary {
            background: var(--primary-color);
            border: none;
        }
        
        .btn-primary:hover {
            background: #ea580c;
        }
        
        .badge-active {
            background: var(--secondary-color);
        }
        
        .badge-inactive {
            background: #6c757d;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-image me-2"></i>Banner Management</h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBannerModal">
                        <i class="fas fa-plus me-2"></i>Add New Banner
                    </button>
                </div>
                
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?= $_SESSION['success_message'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['success_message']); ?>
                <?php endif; ?>
                
                <div class="row">
                    <?php foreach ($banners as $banner): ?>
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="banner-preview" style="background-color: <?= htmlspecialchars($banner['background_color'] ?? '#f97316') ?>; 
                                     <?= !empty($banner['background_image']) ? 'background-image: url(../' . htmlspecialchars($banner['background_image']) . ');' : '' ?>">
                                    <div class="banner-content" style="color: <?= htmlspecialchars($banner['text_color'] ?? '#ffffff') ?>">
                                        <h3><?= htmlspecialchars($banner['title']) ?></h3>
                                        <p><?= htmlspecialchars($banner['subtitle']) ?></p>
                                        <?php if (!empty($banner['button_text'])): ?>
                                            <button class="btn btn-light"><?= htmlspecialchars($banner['button_text']) ?></button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="badge <?= $banner['is_active'] ? 'badge-active' : 'badge-inactive' ?>">
                                            <?= $banner['is_active'] ? 'Active' : 'Inactive' ?>
                                        </span>
                                        <small class="text-muted">Order: <?= $banner['display_order'] ?></small>
                                    </div>
                                    <p class="mb-2"><strong>Type:</strong> <?= ucfirst($banner['banner_type']) ?></p>
                                    <p class="mb-3"><strong>Position:</strong> <?= $banner['position'] ?></p>
                                    <div class="btn-group w-100">
                                        <button class="btn btn-sm btn-outline-primary" onclick="editBanner(<?= htmlspecialchars(json_encode($banner)) ?>)">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <form method="POST" class="d-inline" style="flex: 1;">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="id" value="<?= $banner['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-secondary w-100">
                                                <i class="fas fa-toggle-on"></i> Toggle
                                            </button>
                                        </form>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Delete this banner?')" style="flex: 1;">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $banner['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger w-100">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </main>
        </div>
    </div>

    <!-- Add Banner Modal -->
    <div class="modal fade" id="addBannerModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Banner</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Title *</label>
                                <input type="text" name="title" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Subtitle</label>
                                <input type="text" name="subtitle" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Banner Type *</label>
                                <select name="banner_type" class="form-select" required>
                                    <option value="promotional">Promotional</option>
                                    <option value="hero">Hero</option>
                                    <option value="announcement">Announcement</option>
                                    <option value="festival">Festival</option>
                                    <option value="seasonal">Seasonal</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Position *</label>
                                <select name="position" class="form-select" required>
                                    <option value="homepage_hero">Homepage Hero</option>
                                    <option value="homepage_promo">Homepage Promo</option>
                                    <option value="homepage_announcement">Homepage Announcement</option>
                                    <option value="delivery_page">Delivery Page</option>
                                    <option value="pickup_page">Pickup Page</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Background Color</label>
                                <input type="color" name="background_color" class="form-control form-control-color" value="#f97316">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Text Color</label>
                                <input type="color" name="text_color" class="form-control form-control-color" value="#ffffff">
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Background Image (Optional)</label>
                                <input type="file" name="background_image" class="form-control" accept="image/*">
                                <small class="text-muted">Recommended size: 1200x400px</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Button Text</label>
                                <input type="text" name="button_text" class="form-control" placeholder="Get it">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Button Link</label>
                                <input type="text" name="button_link" class="form-control" placeholder="/products">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Display Order</label>
                                <input type="number" name="display_order" class="form-control" value="0">
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-check mt-4">
                                    <input type="checkbox" name="is_active" class="form-check-input" id="is_active_add" checked>
                                    <label class="form-check-label" for="is_active_add">Active</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Banner</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Banner Modal -->
    <div class="modal fade" id="editBannerModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    <input type="hidden" name="existing_image" id="edit_existing_image">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Banner</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Title *</label>
                                <input type="text" name="title" id="edit_title" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Subtitle</label>
                                <input type="text" name="subtitle" id="edit_subtitle" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Banner Type *</label>
                                <select name="banner_type" id="edit_banner_type" class="form-select" required>
                                    <option value="promotional">Promotional</option>
                                    <option value="hero">Hero</option>
                                    <option value="announcement">Announcement</option>
                                    <option value="festival">Festival</option>
                                    <option value="seasonal">Seasonal</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Position *</label>
                                <select name="position" id="edit_position" class="form-select" required>
                                    <option value="homepage_hero">Homepage Hero</option>
                                    <option value="homepage_promo">Homepage Promo</option>
                                    <option value="homepage_announcement">Homepage Announcement</option>
                                    <option value="delivery_page">Delivery Page</option>
                                    <option value="pickup_page">Pickup Page</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Background Color</label>
                                <input type="color" name="background_color" id="edit_background_color" class="form-control form-control-color">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Text Color</label>
                                <input type="color" name="text_color" id="edit_text_color" class="form-control form-control-color">
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Background Image (Optional)</label>
                                <input type="file" name="background_image" class="form-control" accept="image/*">
                                <small class="text-muted">Leave empty to keep current image</small>
                                <div id="current_image_preview" class="mt-2"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Button Text</label>
                                <input type="text" name="button_text" id="edit_button_text" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Button Link</label>
                                <input type="text" name="button_link" id="edit_button_link" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Display Order</label>
                                <input type="number" name="display_order" id="edit_display_order" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-check mt-4">
                                    <input type="checkbox" name="is_active" class="form-check-input" id="edit_is_active">
                                    <label class="form-check-label" for="edit_is_active">Active</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Banner</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editBanner(banner) {
            document.getElementById('edit_id').value = banner.id;
            document.getElementById('edit_title').value = banner.title;
            document.getElementById('edit_subtitle').value = banner.subtitle || '';
            document.getElementById('edit_banner_type').value = banner.banner_type;
            document.getElementById('edit_position').value = banner.position;
            document.getElementById('edit_background_color').value = banner.background_color || '#f97316';
            document.getElementById('edit_text_color').value = banner.text_color || '#ffffff';
            document.getElementById('edit_button_text').value = banner.button_text || '';
            document.getElementById('edit_button_link').value = banner.button_link || '';
            document.getElementById('edit_display_order').value = banner.display_order;
            document.getElementById('edit_is_active').checked = banner.is_active == 1;
            document.getElementById('edit_existing_image').value = banner.background_image || '';
            
            const previewDiv = document.getElementById('current_image_preview');
            if (banner.background_image) {
                previewDiv.innerHTML = '<img src="../' + banner.background_image + '" class="img-thumbnail" style="max-height: 100px;">';
            } else {
                previewDiv.innerHTML = '';
            }
            
            new bootstrap.Modal(document.getElementById('editBannerModal')).show();
        }
    </script>
</body>
</html>
