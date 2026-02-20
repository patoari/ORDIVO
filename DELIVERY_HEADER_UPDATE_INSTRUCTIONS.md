# Delivery Page Header Update Instructions

The delivery.php page needs the same mobile header structure as index.php.

## Changes Required:

### 1. Replace the current header HTML (lines ~630-700) with the mobile-responsive structure from index.php

The new header should have:
- **Row 1 (mobile-header-top)**: Address + Login button
- **Row 2 (mobile-header-middle)**: Logo + Hamburger + Filters + Action Icons (Language, Favorites, Cart)
- Desktop view remains unchanged

### 2. Add mobile-specific CSS from index.php

Copy all mobile header styles including:
- `.mobile-header-top`
- `.mobile-header-middle`
- `.mobile-header-left`
- `.mobile-header-right`
- `.mobile-nav-toggle`
- Responsive breakpoints for mobile (@media max-width: 768px)

### 3. Update the nav-tabs-container

Add hamburger and filters buttons functionality with sliding sidebar menu.

### 4. Add JavaScript for mobile menu

Copy the hamburger menu toggle JavaScript from index.php including:
- Sliding sidebar animation
- Backdrop overlay
- Body scroll lock
- Multiple close methods

## Files to Reference:
- Source: `customer/index.php` (lines 540-750 for HTML, lines 540-1100 for CSS)
- Target: `customer/delivery.php`

Would you like me to proceed with applying these changes to delivery.php?
