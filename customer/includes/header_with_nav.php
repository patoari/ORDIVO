<!-- Combined Header & Navigation with Inline CSS -->
<style>
    /* ========== HEADER & NAVIGATION STYLES ========== */
    
    /* ========== DESKTOP STYLES (Default) ========== */
    
    /* Header */
    .header {
        background: white;
        padding: 0;
        box-shadow: 0 2px 4px #e5e7eb;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 1000;
        height: 100px;
    }

    .header .navbar {
        height: 100px;
        padding: 0 1rem;
    }

    .header .container-fluid {
        height: 100%;
    }

    .header .navbar-expand-lg {
        height: 100%;
        align-items: center;
    }

    .navbar-brand {
        font-size: 1.8rem;
        font-weight: 700;
        color: #10b981 !important;
        text-decoration: none;
        display: flex;
        align-items: center;
        height: fit-content;
        margin-right: 2rem;
    }

    .navbar-brand:hover {
        color: #10b981 !important;
        text-decoration: none;
    }

    .navbar-brand img {
        height: 100px;
        width: auto;
        margin-right: 12px;
        object-fit: contain;
        animation: logoFloat 3s ease-in-out infinite;
        transition: all 0.3s ease;
    }

    .navbar-brand img:hover {
        transform: scale(1.15) rotate(5deg);
    }

    @keyframes logoFloat {
        0%, 100% { transform: translateY(0px) rotate(0deg); }
        25% { transform: translateY(-4px) rotate(1deg); }
        50% { transform: translateY(-6px) rotate(0deg); }
        75% { transform: translateY(-4px) rotate(-1deg); }
    }

    .navbar-brand i.fa-utensils {
        animation: logoPulse 2s ease-in-out infinite;
        font-size: 2.5rem !important;
        color: #10b981;
    }

    @keyframes logoPulse {
        0%, 100% { transform: scale(1); opacity: 1; }
        50% { transform: scale(1.1); opacity: 0.8; }
    }

    .location-display {
        display: flex;
        align-items: center;
        color: #6b7280;
        font-size: 0.9rem;
        cursor: pointer;
        padding: 0.75rem 1rem;
        border-radius: 8px;
        transition: all 0.3s ease;
        height: fit-content;
        border: 2px solid #10b981;
        background: white;
    }

    .location-display:hover {
        background: #10b981;
        color: white;
        border-color: #059669;
    }

    .location-display:hover i {
        color: white;
    }

    .user-menu {
        display: flex;
        align-items: center;
        gap: 1rem;
        height: fit-content;
    }

    .btn-user {
        background: white;
        border: 2px solid #10b981;
        color: #374151;
        padding: 0.75rem 1rem;
        border-radius: 8px;
        text-decoration: none;
        font-size: 0.9rem;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        height: fit-content;
        cursor: pointer;
    }

    .btn-user.btn-icon-only {
        padding: 0.75rem;
        min-width: 44px;
        justify-content: center;
    }

    .btn-user.btn-icon-only i {
        margin: 0 !important;
    }

    .btn-user:hover {
        background: #10b981;
        color: white;
        border-color: #059669;
    }

    .btn-user:hover i {
        color: white;
    }

    .dropdown-toggle {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .dropdown-toggle::after {
        margin-left: 0.5rem;
    }

    /* Navigation Tabs */
    .nav-tabs-container {
        background: white;
        border-bottom: 1px solid #e9ecef;
        padding: 0 1rem;
        height: 60px;
        position: fixed;
        top: 100px;
        left: 0;
        right: 0;
        z-index: 999;
        box-shadow: 0 2px 4px #e5e7eb;
        border-top: 2px solid transparent;
        border-bottom: 2px solid transparent;
        background: #10b981;
        background-origin: border-box;
        background-clip: padding-box, border-box;
        animation: navbarBorderPulse 3s ease-in-out infinite;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    @keyframes navbarBorderPulse {
        0%, 100% {
            background: #10b981;
            background-size: 100% 100%, 200% 200%;
            background-position: 0 0, 0 0;
        }
        50% {
            background: #10b981;
            background-size: 100% 100%, 200% 200%;
            background-position: 0 0, -100% -100%;
        }
    }

    .nav-tabs {
        border-bottom: none;
        flex: 1;
        display: flex;
        justify-content: flex-start;
        align-items: center;
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .nav-tabs .nav-link {
        border: none;
        color: #e5e7eb;
        font-weight: 500;
        padding: 0.75rem 1.5rem;
        margin-right: 1rem;
        border: 2px solid transparent;
        border-radius: 8px;
        background: none;
        transition: all 0.3s ease;
        position: relative;
        white-space: nowrap;
    }

    .nav-tabs .nav-link.active {
        color: #ffffff;
        border: 2px solid #ffffff;
        background: #059669;
        font-weight: 600;
    }

    .nav-tabs .nav-link:hover {
        color: #ffffff;
        border: 2px solid #ffffff;
        background: #059669;
    }

    /* Desktop: Keep normal horizontal layout */
    @media (min-width: 769px) {
        #mainNavTabs {
            position: static !important;
            left: auto !important;
            width: auto !important;
            height: auto !important;
            flex-direction: row !important;
            padding: 0 !important;
            box-shadow: none !important;
            background: transparent !important;
            top: auto !important;
        }
    }

    /* Mobile Navigation Toggle */
    .mobile-nav-toggle {
        background: white;
        border: 2px solid #10b981;
        border-radius: 8px;
        color: #10b981;
        width: 40px;
        height: 40px;
        font-size: 1.1rem;
        cursor: pointer;
        transition: all 0.3s ease;
        flex-shrink: 0;
        display: none;
    }

    .mobile-nav-toggle:hover {
        background: #10b981;
        color: white;
        transform: scale(1.05);
    }

    .mobile-nav-toggle.active {
        background: #10b981;
        color: white;
    }

    .mobile-nav-toggle.active {
        background: #10b981;
        color: white;
    }

    /* Hide mobile elements on desktop */
    .mobile-only,
    .mobile-header-top,
    .mobile-header-middle,
    .mobile-header-bottom,
    .mobile-header-left,
    .mobile-header-right {
        display: none !important;
    }

    /* Desktop-only elements */
    .desktop-only {
        display: block !important;
    }

    /* ========== TABLET STYLES (768px - 1024px) ========== */
    @media (max-width: 1024px) and (min-width: 769px) {
        .navbar-brand img {
            height: 80px;
        }

        .navbar-brand i.fa-utensils {
            font-size: 2rem !important;
        }

        .location-display {
            font-size: 0.85rem;
            padding: 0.6rem 0.8rem;
        }

        .btn-user {
            padding: 0.6rem 0.8rem;
            font-size: 0.85rem;
        }

        .nav-tabs .nav-link {
            padding: 0.6rem 1rem;
            font-size: 0.9rem;
            margin-right: 0.5rem;
        }

        .user-menu {
            gap: 0.75rem;
        }
    }

    /* ========== MOBILE & TABLET STYLES (≤768px) ========== */
    @media (max-width: 768px) {
        /* Hide desktop header */
        .desktop-only {
            display: none !important;
        }

        /* Show mobile elements */
        .mobile-only {
            display: block !important;
        }

        /* Header adjustments */
        .header {
            height: auto;
            min-height: auto;
            padding: 0;
        }

        .header .navbar {
            height: auto;
            padding: 0;
            flex-direction: column;
            align-items: stretch !important;
        }

        .header .container-fluid {
            height: auto;
            padding: 0;
        }

        /* Mobile Header Top Row - Location + Login */
        .mobile-header-top {
            display: flex !important;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            height: 44px;
            padding: 0 0.75rem;
            background: #f8f9fa;
            border-bottom: 1px solid #e5e7eb;
        }

        .location-display {
            flex: 1;
            font-size: 0.75rem;
            padding: 0.4rem 0.75rem;
            border-radius: 20px;
            background: white;
            border: 1px solid #e5e7eb;
            max-width: calc(100% - 60px);
            height: 32px;
            display: flex;
            align-items: center;
        }

        .location-display i {
            font-size: 0.7rem;
            flex-shrink: 0;
        }

        .location-display span {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            flex: 1;
            font-size: 0.7rem;
        }

        .location-display:hover {
            background: #f8f9fa;
            border-color: #10b981;
        }

        .mobile-login-btn {
            flex-shrink: 0;
            margin-left: 0.5rem;
            display: block !important;
        }

        .mobile-login-btn .btn-user {
            padding: 0.4rem 0.75rem;
            height: 32px;
            min-width: 40px;
            border-radius: 20px;
            font-size: 0.75rem;
        }

        /* Mobile Header Middle Row - Logo + Hamburger + Icons */
        .mobile-header-middle {
            display: flex !important;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            height: 70px;
            padding: 0 0.5rem;
            background: white;
            border-bottom: 2px solid #10b981;
        }

        .mobile-header-left {
            display: flex !important;
            align-items: center;
            gap: 0.5rem;
            flex: 0 0 auto;
            min-width: 0;
        }

        .mobile-header-middle .navbar-brand {
            margin: 0;
            padding: 0;
        }

        .mobile-header-right {
            display: flex !important;
            align-items: center;
            gap: 0.25rem;
            flex-shrink: 0;
        }

        .mobile-header-right .btn-user,
        .mobile-header-right .dropdown button {
            width: 36px;
            height: 36px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            flex-shrink: 0;
            position: relative;
        }

        .mobile-header-right .btn-user i,
        .mobile-header-right .dropdown button i {
            font-size: 1rem;
            margin: 0;
        }

        .mobile-header-right .dropdown-toggle::after {
            display: none;
        }

        /* Cart badge */
        .cart-badge {
            position: absolute;
            top: -4px;
            right: -4px;
            background: #dc3545;
            color: white;
            font-size: 0.65rem;
            font-weight: 700;
            padding: 0.15rem 0.35rem;
            border-radius: 10px;
            min-width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid white;
        }

        .navbar-brand {
            margin-right: 0;
        }

        .navbar-brand img {
            height: 100px;
        }

        .navbar-brand i.fa-utensils {
            font-size: 3rem !important;
        }

        /* Mobile Navigation Container */
        .nav-tabs-container {
            top: 114px; /* Row1(44px) + Row2(70px) */
            background: transparent;
            border: none;
            box-shadow: none;
            padding: 0;
            height: 0;
            animation: none;
            overflow: visible;
        }

        .nav-tabs-container .container-fluid {
            padding: 0;
            overflow: visible;
            position: static;
        }

        /* Mobile Menu Backdrop */
        .mobile-menu-backdrop {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9998;
            pointer-events: auto;
        }

        .mobile-menu-backdrop.show {
            display: block;
        }

        /* Sliding Sidebar Menu for Navigation */
        #mainNavTabs {
            display: flex !important;
            position: fixed !important;
            top: 114px !important;
            left: -280px !important;
            width: 280px !important;
            max-width: 80vw !important;
            height: calc(100vh - 114px) !important;
            background: #10b981 !important;
            flex-direction: column !important;
            padding: 1.5rem 1rem !important;
            box-shadow: 2px 0 10px rgba(0,0,0,0.3) !important;
            overflow-y: auto !important;
            z-index: 10000 !important;
            transition: left 0.3s ease-in-out !important;
            margin: 0 !important;
            list-style: none !important;
            pointer-events: auto !important;
        }

        #mainNavTabs.show {
            left: 0 !important;
        }

        #mainNavTabs .nav-item {
            width: 100%;
            margin-bottom: 0.5rem;
        }

        #mainNavTabs .nav-link {
            width: 100%;
            margin-right: 0;
            text-align: left;
            padding: 1rem;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            cursor: pointer;
            pointer-events: auto;
            text-decoration: none;
            color: #e5e7eb;
        }

        #mainNavTabs .nav-link i {
            margin-right: 0.75rem;
            width: 20px;
            text-align: center;
        }

        #mainNavTabs .nav-link:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateX(5px);
            color: #ffffff;
        }

        #mainNavTabs .nav-link.active {
            background: rgba(255, 255, 255, 0.3);
            border: 2px solid #ffffff;
            color: #ffffff;
        }
    }

    /* ========== SMALL MOBILE (≤480px) ========== */
    @media (max-width: 480px) {
        .mobile-header-middle {
            padding: 0 0.4rem;
            height: 65px;
        }

        .mobile-header-left {
            gap: 0.4rem;
        }

        .navbar-brand img {
            height: 90px !important;
        }

        .navbar-brand i.fa-utensils {
            font-size: 2.75rem !important;
        }

        .mobile-header-right {
            gap: 0.2rem;
        }

        .mobile-header-right .btn-user,
        .mobile-header-right .dropdown button {
            width: 34px;
            height: 34px;
        }

        .mobile-header-right .btn-user i,
        .mobile-header-right .dropdown button i {
            font-size: 0.95rem;
        }

        .location-display {
            font-size: 0.7rem;
            padding: 0.35rem 0.6rem;
        }

        .location-display i {
            font-size: 0.65rem;
        }

        .location-display span {
            font-size: 0.65rem;
        }

        .mobile-login-btn .btn-user {
            padding: 0.35rem 0.6rem;
            height: 30px;
            min-width: 36px;
            font-size: 0.7rem;
        }

        .nav-tabs {
            width: 260px;
        }

        .nav-tabs .nav-link {
            padding: 0.85rem;
            font-size: 0.95rem;
        }
    }

    /* ========== EXTRA SMALL MOBILE (≤360px) ========== */
    @media (max-width: 360px) {
        .mobile-header-top {
            height: 40px;
            padding: 0 0.5rem;
        }

        .mobile-header-middle {
            padding: 0 0.35rem;
            height: 60px;
        }

        .mobile-header-left {
            gap: 0.35rem;
        }

        .navbar-brand img {
            height: 80px !important;
        }

        .navbar-brand i.fa-utensils {
            font-size: 2.5rem !important;
        }

        .mobile-header-right {
            gap: 0.15rem;
        }

        .mobile-header-right .btn-user,
        .mobile-header-right .dropdown button {
            width: 32px;
            height: 32px;
        }

        .mobile-header-right .btn-user i,
        .mobile-header-right .dropdown button i {
            font-size: 0.9rem;
        }

        .location-display {
            font-size: 0.65rem;
            padding: 0.3rem 0.5rem;
            height: 28px;
        }

        .location-display i {
            font-size: 0.6rem;
        }

        .location-display span {
            font-size: 0.6rem;
        }

        .mobile-login-btn .btn-user {
            padding: 0.3rem 0.5rem;
            height: 28px;
            min-width: 32px;
            font-size: 0.65rem;
        }

        .nav-tabs {
            width: 240px;
        }

        .nav-tabs .nav-link {
            padding: 0.75rem;
            font-size: 0.9rem;
        }

        .nav-tabs-container {
            top: 100px; /* Adjusted for smaller header */
        }

        .nav-tabs {
            top: 100px;
            height: calc(100vh - 100px);
        }
    }

    /* ========== LARGE DESKTOP (≥1440px) ========== */
    @media (min-width: 1440px) {
        .header .navbar {
            padding: 0 2rem;
        }

        .nav-tabs-container {
            padding: 0 2rem;
        }

        .navbar-brand img {
            height: 110px;
        }

        .navbar-brand i.fa-utensils {
            font-size: 2.75rem !important;
        }

        .location-display {
            font-size: 1rem;
            padding: 0.85rem 1.2rem;
        }

        .btn-user {
            padding: 0.85rem 1.2rem;
            font-size: 1rem;
        }

        .nav-tabs .nav-link {
            padding: 0.85rem 1.75rem;
            font-size: 1rem;
        }
    }
</style>

<!-- Mobile Menu Backdrop -->
<div class="mobile-menu-backdrop" id="mobileMenuBackdrop"></div>

<!-- Header -->
<header class="header">
    <!-- Desktop Header -->
    <div class="container-fluid desktop-only">
        <nav class="navbar navbar-expand-lg d-flex justify-content-between align-items-center">
            <!-- Logo on Left -->
            <a class="navbar-brand" href="index.php">
                <?php if (!empty($siteLogo) && $siteLogo !== '🍔' && $siteLogo !== '🍽️'): ?>
                    <img src="<?= htmlspecialchars($siteLogo) ?>" alt="<?= htmlspecialchars($siteName) ?>" 
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='inline';">
                    <i class="fas fa-utensils" style="display: none;"></i>
                <?php else: ?>
                    <i class="fas fa-utensils"></i>
                <?php endif; ?>
            </a>
            
            <!-- Location in Center -->
            <div class="location-display" data-bs-toggle="modal" data-bs-target="#locationModal">
                <i class="fas fa-map-marker-alt me-2"></i>
                <span id="currentLocation"><?= htmlspecialchars($userLocation) ?></span>
                <i class="fas fa-chevron-down ms-2"></i>
            </div>
            
            <!-- Action Buttons on Right -->
            <div class="user-menu">
                <!-- User Account -->
                <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
                    <div class="dropdown">
                        <button class="btn-user dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i>Account
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>My Profile</a></li>
                            <li><a class="dropdown-item" href="orders.php"><i class="fas fa-receipt me-2"></i>My Orders</a></li>
                            <li><a class="dropdown-item" href="favorites.php"><i class="fas fa-heart me-2"></i>Favorites</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="../auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="../auth/login.php" class="btn-user">
                        <i class="fas fa-user me-1"></i>Account
                    </a>
                <?php endif; ?>

                <!-- Language Selector -->
                <div class="dropdown">
                    <button class="btn-user dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-globe me-1"></i>EN
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="#" onclick="changeLanguage('en'); return false;">🇬🇧 English</a></li>
                        <li><a class="dropdown-item" href="#" onclick="changeLanguage('bn'); return false;">🇧🇩 বাংলা</a></li>
                    </ul>
                </div>

                <!-- Favorites -->
                <a href="favorites.php" class="btn-user btn-icon-only" title="Favorites">
                    <i class="fas fa-heart"></i>
                </a>
                
                <!-- Cart -->
                <a href="cart.php" class="btn-user btn-icon-only" title="Cart">
                    <i class="fas fa-shopping-cart"></i>
                </a>
            </div>
        </nav>
    </div>

    <!-- Mobile Header -->
    <div class="mobile-only">
        <!-- Row 1: Location + Login -->
        <div class="mobile-header-top">
            <div class="location-display" data-bs-toggle="modal" data-bs-target="#locationModal">
                <i class="fas fa-map-marker-alt me-1"></i>
                <span id="currentLocationMobile"><?= htmlspecialchars($userLocation) ?></span>
            </div>
            <div class="mobile-login-btn">
                <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
                    <div class="dropdown">
                        <button class="btn-user dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                            <li><a class="dropdown-item" href="orders.php">Orders</a></li>
                            <li><a class="dropdown-item" href="../auth/logout.php">Logout</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="../auth/login.php" class="btn-user">
                        <i class="fas fa-user"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Row 2: Logo + All Icons -->
        <div class="mobile-header-middle">
            <!-- Group 1: Logo alone (left) -->
            <div class="mobile-header-left">
                <a class="navbar-brand" href="index.php">
                    <?php if (!empty($siteLogo) && $siteLogo !== '🍔' && $siteLogo !== '🍽️'): ?>
                        <img src="<?= htmlspecialchars($siteLogo) ?>" alt="<?= htmlspecialchars($siteName) ?>">
                    <?php else: ?>
                        <i class="fas fa-utensils"></i>
                    <?php endif; ?>
                </a>
            </div>
            
            <!-- Group 2: All icons together (right) -->
            <div class="mobile-header-right">
                <!-- Hamburger -->
                <button class="mobile-nav-toggle btn-user" id="mobileNavToggle">
                    <i class="fas fa-bars"></i>
                </button>
                
                <!-- Filter -->
                <button class="btn-user" id="mobileFiltersBtn">
                    <i class="fas fa-filter"></i>
                </button>

                <!-- Language -->
                <div class="dropdown">
                    <button class="btn-user dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-globe"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="#" onclick="changeLanguage('en'); return false;">English</a></li>
                        <li><a class="dropdown-item" href="#" onclick="changeLanguage('bn'); return false;">বাংলা</a></li>
                    </ul>
                </div>

                <!-- Favorites -->
                <a href="favorites.php" class="btn-user">
                    <i class="fas fa-heart"></i>
                </a>

                <!-- Cart -->
                <a href="cart.php" class="btn-user">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-badge" id="cartBadge" style="display: none;">0</span>
                </a>
            </div>
        </div>
    </div>
</header>

<!-- Navigation Tabs -->
<div class="nav-tabs-container">
    <div class="container-fluid">
        <ul class="nav nav-tabs" id="mainNavTabs">
            <li class="nav-item">
                <a class="nav-link active" href="index.php">
                    <i class="fas fa-home me-2"></i>Home
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="delivery.php">
                    <i class="fas fa-motorcycle me-2"></i>Delivery
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="pickup.php">
                    <i class="fas fa-shopping-bag me-2"></i>Pickup
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="ordivomart.php">
                    <i class="fas fa-store me-2"></i>OrdivoMart
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="shops.php">
                    <i class="fas fa-shop me-2"></i>Shops
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="products.php">
                    <i class="fas fa-utensils me-2"></i>All Products
                </a>
            </li>
        </ul>
    </div>
</div>

<script>
// Mobile navigation and filter menu management
document.addEventListener('DOMContentLoaded', function() {
    console.log('Header navigation script loaded');
    
    // ========== HAMBURGER MENU (Navigation) ==========
    const mobileNavToggle = document.getElementById('mobileNavToggle');
    const mainNavTabs = document.getElementById('mainNavTabs');
    const mobileMenuBackdrop = document.getElementById('mobileMenuBackdrop');
    
    console.log('Hamburger elements:', {
        toggle: mobileNavToggle,
        tabs: mainNavTabs,
        backdrop: mobileMenuBackdrop
    });
    
    if (mobileNavToggle && mainNavTabs && mobileMenuBackdrop) {
        console.log('Hamburger menu initialized');
        
        let isMenuOpen = false;
        
        // Toggle navigation menu
        mobileNavToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Hamburger clicked, current state:', isMenuOpen);
            
            if (isMenuOpen) {
                closeNavMenu();
            } else {
                openNavMenu();
            }
        });
        
        // Close menu when clicking on a nav link
        const navLinks = mainNavTabs.querySelectorAll('.nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                console.log('Nav link clicked:', this.href);
                // Allow navigation
                closeNavMenu();
            });
        });
        
        // Close menu on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && isMenuOpen) {
                console.log('Escape key pressed');
                closeNavMenu();
            }
        });
        
        // Close menu when clicking outside - use setTimeout to avoid conflicts
        setTimeout(() => {
            document.addEventListener('click', function(e) {
                if (!isMenuOpen) return;
                
                const clickedInsideMenu = mainNavTabs.contains(e.target);
                const clickedToggle = mobileNavToggle.contains(e.target);
                
                console.log('Document clicked:', {
                    isMenuOpen,
                    clickedInsideMenu,
                    clickedToggle,
                    target: e.target
                });
                
                if (!clickedInsideMenu && !clickedToggle) {
                    console.log('Closing menu - clicked outside');
                    closeNavMenu();
                }
            });
        }, 100);
        });
    } else {
        console.error('Hamburger menu elements not found!', {
            toggle: !!mobileNavToggle,
            tabs: !!mainNavTabs,
            backdrop: !!mobileMenuBackdrop
        });
    }
    
    function openNavMenu() {
        console.log('=== OPENING NAV MENU ===');
        isMenuOpen = true;
        
        // Add show class
        mainNavTabs.classList.add('show');
        mobileNavToggle.classList.add('active');
        
        // Force styles directly - z-index 10000 to be above everything
        mainNavTabs.style.cssText = `
            position: fixed !important;
            top: 114px !important;
            left: 0 !important;
            width: 280px !important;
            height: calc(100vh - 114px) !important;
            background: #10b981 !important;
            z-index: 10000 !important;
            display: flex !important;
            flex-direction: column !important;
            padding: 1.5rem 1rem !important;
            box-shadow: 2px 0 10px rgba(0,0,0,0.3) !important;
            overflow-y: auto !important;
            transition: left 0.3s ease-in-out !important;
        `;
        
        console.log('=== NAV MENU OPENED ===');
    }
    
    function closeNavMenu() {
        console.log('=== CLOSING NAV MENU ===');
        isMenuOpen = false;
        
        mainNavTabs.classList.remove('show');
        mobileNavToggle.classList.remove('active');
        
        // Reset to hidden position
        mainNavTabs.style.left = '-280px';
    }
    
    // ========== FILTER BUTTON (if exists on page) ==========
    const mobileFiltersBtn = document.getElementById('mobileFiltersBtn');
    const mobileFiltersModal = document.getElementById('mobileFiltersModal');
    const mobileFiltersClose = document.getElementById('mobileFiltersClose');
    
    console.log('Filter elements:', {
        button: mobileFiltersBtn,
        modal: mobileFiltersModal,
        close: mobileFiltersClose
    });
    
    if (mobileFiltersBtn && mobileFiltersModal) {
        console.log('Filter button initialized');
        
        // Open filters modal
        mobileFiltersBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            console.log('Filter button clicked');
            mobileFiltersModal.classList.add('show');
            document.body.style.overflow = 'hidden';
        });
        
        // Close filters modal
        if (mobileFiltersClose) {
            mobileFiltersClose.addEventListener('click', function() {
                console.log('Filter close clicked');
                closeFiltersModal();
            });
        }
        
        // Close when clicking on modal backdrop
        mobileFiltersModal.addEventListener('click', function(e) {
            if (e.target === mobileFiltersModal) {
                console.log('Filter backdrop clicked');
                closeFiltersModal();
            }
        });
        
        // Close on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && mobileFiltersModal.classList.contains('show')) {
                console.log('Escape key pressed (filters)');
                closeFiltersModal();
            }
        });
    } else {
        console.log('Filter modal not found (this is normal if not on a page with filters)');
    }
    
    function closeFiltersModal() {
        console.log('Closing filters modal');
        if (mobileFiltersModal) {
            mobileFiltersModal.classList.remove('show');
            document.body.style.overflow = '';
        }
    }
    
    // ========== ACTIVE TAB HIGHLIGHTING ==========
    const currentPage = window.location.pathname.split('/').pop() || 'index.php';
    const navLinks = document.querySelectorAll('#mainNavTabs .nav-link');
    
    navLinks.forEach(link => {
        link.classList.remove('active');
        const linkHref = link.getAttribute('href');
        
        if (linkHref === currentPage || 
            (currentPage === '' && linkHref === 'index.php') ||
            (currentPage === 'index.php' && linkHref === 'index.php')) {
            link.classList.add('active');
        }
    });
    
    // ========== CART BADGE UPDATE ==========
    function updateCartBadge() {
        const cartBadge = document.getElementById('cartBadge');
        if (cartBadge) {
            try {
                const cart = JSON.parse(localStorage.getItem('ordivo_cart') || '[]');
                const totalItems = cart.reduce((sum, item) => sum + (item.quantity || 0), 0);
                
                if (totalItems > 0) {
                    cartBadge.textContent = totalItems > 99 ? '99+' : totalItems;
                    cartBadge.style.display = 'flex';
                } else {
                    cartBadge.style.display = 'none';
                }
            } catch (e) {
                console.error('Error updating cart badge:', e);
                cartBadge.style.display = 'none';
            }
        }
    }
    
    // Update cart badge on page load
    updateCartBadge();
    
    // Listen for cart updates
    window.addEventListener('storage', function(e) {
        if (e.key === 'ordivo_cart') {
            updateCartBadge();
        }
    });
    
    // Custom event for cart updates on same page
    window.addEventListener('cartUpdated', updateCartBadge);
    
    // ========== PREVENT BODY SCROLL WHEN MENUS OPEN ==========
    // Ensure body scroll is restored when page loads
    document.body.style.overflow = '';
});

// Language change function
function changeLanguage(lang) {
    console.log('Language changed to:', lang);
    // Store language preference
    localStorage.setItem('ordivo_language', lang);
    
    // Show notification
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'success',
            title: lang === 'en' ? 'Language Changed' : 'ভাষা পরিবর্তন',
            text: lang === 'en' ? 'Language changed to English' : 'ভাষা বাংলায় পরিবর্তন করা হয়েছে',
            timer: 2000,
            showConfirmButton: false
        });
    }
    
    // Reload page to apply language (implement actual translation logic here)
    // setTimeout(() => location.reload(), 2000);
}

// Helper function to dispatch cart update event
function notifyCartUpdate() {
    window.dispatchEvent(new Event('cartUpdated'));
}
</script>
