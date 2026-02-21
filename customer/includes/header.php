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

        <!-- Row 2: Logo + Hamburger + Icons -->
        <div class="mobile-header-middle">
            <div class="mobile-header-left">
                <a class="navbar-brand" href="index.php">
                    <?php if (!empty($siteLogo) && $siteLogo !== '🍔' && $siteLogo !== '🍽️'): ?>
                        <img src="<?= htmlspecialchars($siteLogo) ?>" alt="<?= htmlspecialchars($siteName) ?>">
                    <?php else: ?>
                        <i class="fas fa-utensils"></i>
                    <?php endif; ?>
                </a>
                <button class="mobile-nav-toggle" id="mobileNavToggle">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            <div class="mobile-header-right">
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

                <!-- Filters -->
                <button class="btn-user" id="mobileFiltersBtn">
                    <i class="fas fa-filter"></i>
                </button>

                <!-- Cart -->
                <a href="cart.php" class="btn-user">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-badge" id="cartBadge" style="display: none;">0</span>
                </a>
            </div>
        </div>
    </div>
</header>
