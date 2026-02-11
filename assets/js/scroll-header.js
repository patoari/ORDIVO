/**
 * Scroll behavior for hiding header and fixing navigation
 * Hides header on scroll down, shows on scroll up
 * Fixes navigation to top when header is hidden
 */
function initScrollBehavior() {
    let lastScrollTop = 0;
    const header = document.querySelector('.header');
    const navTabs = document.querySelector('.nav-tabs-container');
    const scrollThreshold = 100;

    if (!header || !navTabs) return;

    window.addEventListener('scroll', function() {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;

        if (scrollTop > scrollThreshold) {
            if (scrollTop > lastScrollTop) {
                // Scrolling down
                header.classList.add('header-hidden');
                navTabs.classList.add('nav-fixed-top');
            } else {
                // Scrolling up
                header.classList.remove('header-hidden');
                navTabs.classList.remove('nav-fixed-top');
            }
        } else {
            // At top of page
            header.classList.remove('header-hidden');
            navTabs.classList.remove('nav-fixed-top');
        }

        lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;
    }, false);
}

// Auto-initialize on DOM load
document.addEventListener('DOMContentLoaded', initScrollBehavior);
