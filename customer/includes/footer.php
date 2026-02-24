<!-- Footer -->
<footer class="footer-section">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-3 mb-4">
                <h5>About ORDIVO</h5>
                <p>Your trusted food and grocery delivery partner in Bangladesh.</p>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <h5>Quick Links</h5>
                <ul class="footer-links">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="delivery.php">Delivery</a></li>
                    <li><a href="pickup.php">Pickup</a></li>
                    <li><a href="ordivomart.php">OrdivoMart</a></li>
                </ul>
            </div>
            <div class="col-md-3 mb-4">
                <h5>Support</h5>
                <ul class="footer-links">
                    <li><a href="help.php">Help Center</a></li>
                    <li><a href="#">Terms & Conditions</a></li>
                    <li><a href="#">Privacy Policy</a></li>
                    <li><a href="#">Contact Us</a></li>
                </ul>
            </div>
            <div class="col-md-3 mb-4">
                <h5>Newsletter</h5>
                <p>Subscribe to get special offers and updates</p>
                <form class="newsletter-form" id="newsletterForm">
                    <input type="email" name="email" id="newsletterEmail" placeholder="Your email" class="form-control mb-2" required>
                    <button type="submit" class="btn btn-primary w-100">Subscribe</button>
                    <div id="newsletterMessage" class="mt-2" style="display: none;"></div>
                </form>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> ORDIVO. All rights reserved.</p>
        </div>
    </div>
</footer>

<script>
// Newsletter Subscription Handler
document.addEventListener('DOMContentLoaded', function() {
    const newsletterForm = document.getElementById('newsletterForm');
    const newsletterEmail = document.getElementById('newsletterEmail');
    const newsletterMessage = document.getElementById('newsletterMessage');

    if (newsletterForm) {
        newsletterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const email = newsletterEmail.value.trim();
            
            // Validate email
            if (!email || !isValidEmail(email)) {
                showMessage('Please enter a valid email address', 'error');
                return;
            }

            // Disable button and show loading
            const submitBtn = newsletterForm.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = 'Subscribing...';

            // Send AJAX request
            fetch('includes/newsletter_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'email=' + encodeURIComponent(email)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage(data.message || 'Thank you for subscribing!', 'success');
                    newsletterForm.reset();
                } else {
                    showMessage(data.message || 'Subscription failed. Please try again.', 'error');
                }
            })
            .catch(error => {
                console.error('Newsletter subscription error:', error);
                showMessage('An error occurred. Please try again later.', 'error');
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            });
        });
    }

    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    function showMessage(message, type) {
        newsletterMessage.textContent = message;
        newsletterMessage.style.display = 'block';
        newsletterMessage.className = 'mt-2 alert alert-' + (type === 'success' ? 'success' : 'danger');
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            newsletterMessage.style.display = 'none';
        }, 5000);
    }
});
</script>
