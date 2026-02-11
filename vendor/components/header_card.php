<?php
/**
 * ORDIVO - Vendor Portal Header Card Component
 * Displays cover photo, profile picture, and welcome message
 * 
 * Required variables:
 * - $vendor: User data array
 * - $vendorCover: Cover photo path (optional)
 * - $userAvatar: Profile picture path (optional)
 * - $pageTitle: Page title (optional, defaults to "Welcome back")
 * - $pageSubtitle: Page subtitle (optional)
 */

$pageTitle = $pageTitle ?? 'Welcome back, ' . htmlspecialchars($vendor['name'] ?? 'Vendor') . '!';
$pageSubtitle = $pageSubtitle ?? "Here's what's happening with your business today";
?>

<!-- Header Card with Cover Photo and Profile Picture -->
<div class="welcome-card">
    <?php if (!empty($vendorCover)): ?>
        <img src="<?= htmlspecialchars($vendorCover) ?>" alt="Cover Photo" class="welcome-card-cover">
    <?php endif; ?>
    
    <div class="welcome-card-content">
        <!-- Profile Picture -->
        <div>
            <?php if (!empty($userAvatar)): ?>
                <img src="<?= htmlspecialchars($userAvatar) ?>" alt="Profile Picture" class="welcome-card-avatar">
            <?php else: ?>
                <div class="welcome-card-avatar-placeholder">
                    <i class="fas fa-user"></i>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Welcome Text -->
        <div class="welcome-card-info">
            <h1 class="mb-2"><?= $pageTitle ?></h1>
            <p class="mb-0 opacity-75"><?= $pageSubtitle ?></p>
        </div>
        
        <!-- Date/Time -->
        <div class="welcome-card-time">
            <div class="h5 mb-0"><?= date('l, F j, Y') ?></div>
            <div class="opacity-75"><?= date('g:i A') ?></div>
        </div>
    </div>
</div>

<style>
    .welcome-card {
        position: relative;
        background: linear-gradient(135deg, var(--ordivo-primary) 0%, var(--ordivo-secondary) 100%);
        color: white;
        border-radius: 15px;
        padding: 0;
        margin-bottom: 2rem;
        box-shadow: 0 10px 30px rgba(226, 27, 112, 0.3);
        overflow: hidden;
        min-height: 200px;
    }

    .welcome-card-cover {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        opacity: 0.3;
        z-index: 0;
    }

    .welcome-card-content {
        position: relative;
        z-index: 1;
        padding: 2rem;
        display: flex;
        align-items: center;
        gap: 2rem;
    }

    .welcome-card-avatar {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        border: 4px solid white;
        object-fit: cover;
        background: white;
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        flex-shrink: 0;
    }

    .welcome-card-avatar-placeholder {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        border: 4px solid white;
        background: rgba(255,255,255,0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.5rem;
        color: white;
        flex-shrink: 0;
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }

    .welcome-card-info {
        flex: 1;
    }

    .welcome-card-time {
        text-align: right;
    }

    @media (max-width: 768px) {
        .welcome-card-content {
            flex-direction: column;
            text-align: center;
        }
        
        .welcome-card-time {
            text-align: center;
        }
    }
</style>
