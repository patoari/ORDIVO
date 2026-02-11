<?php
/**
 * ORDIVO - Help & Support Page
 * Customer support and FAQ
 */

require_once '../config/db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: ../auth/login.php');
    exit;
}

$faqs = [
    [
        'question' => 'How do I place an order?',
        'answer' => 'Browse restaurants on our homepage, select items from the menu, add them to your cart, and proceed to checkout. You can pay online or choose cash on delivery.'
    ],
    [
        'question' => 'What are the delivery charges?',
        'answer' => 'Delivery charges vary by restaurant and distance. You can see the exact delivery fee before placing your order. Some restaurants offer free delivery on orders above a certain amount.'
    ],
    [
        'question' => 'How can I track my order?',
        'answer' => 'Once you place an order, you can track it in real-time from the "My Orders" section. You\'ll also receive SMS and email updates about your order status.'
    ],
    [
        'question' => 'Can I cancel my order?',
        'answer' => 'You can cancel your order within a few minutes of placing it, before the restaurant starts preparing it. Go to "My Orders" and click the cancel button if available.'
    ],
    [
        'question' => 'What payment methods do you accept?',
        'answer' => 'We accept credit/debit cards, digital wallets, UPI, net banking, and cash on delivery. You can also use your ORDIVO wallet balance to pay for orders.'
    ],
    [
        'question' => 'How do I add money to my wallet?',
        'answer' => 'Go to the "Wallet" section from your profile menu and click "Add Money". You can add money using any of our supported payment methods.'
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Help & Support - ORDIVO</title>
    
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

        .help-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px #e5e7eb;
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .contact-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .contact-card {
            background: var(--ordivo-light);
            border: 2px solid var(--ordivo-primary);
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .contact-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px #f97316;
        }

        .contact-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: var(--ordivo-primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin: 0 auto 1rem;
        }

        .faq-item {
            border: 1px solid #e9ecef;
            border-radius: 10px;
            margin-bottom: 1rem;
            overflow: hidden;
        }

        .faq-question {
            background: #f8f9fa;
            padding: 1.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            width: 100%;
            text-align: left;
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .faq-question:hover {
            background: var(--ordivo-light);
            color: var(--ordivo-primary);
        }

        .faq-answer {
            padding: 1.5rem;
            background: white;
            display: none;
            border-top: 1px solid #e9ecef;
        }

        .faq-answer.show {
            display: block;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--ordivo-dark);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .section-title i {
            color: var(--ordivo-primary);
        }

        .btn-primary {
            background: var(--ordivo-primary);
            border-color: var(--ordivo-primary);
            border-radius: 25px;
            padding: 0.75rem 2rem;
            font-weight: 600;
        }

        .btn-primary:hover {
            background: var(--ordivo-secondary);
            border-color: var(--ordivo-secondary);
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
                        <i class="fas fa-question-circle me-2"></i>Help & Support
                    </h1>
                    <p class="mb-0 opacity-75">We're here to help you with any questions</p>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Contact Options -->
        <div class="help-card">
            <h3 class="section-title">
                <i class="fas fa-headset"></i>Contact Us
            </h3>
            
            <div class="contact-options">
                <div class="contact-card" onclick="openChat()">
                    <div class="contact-icon">
                        <i class="fas fa-comments"></i>
                    </div>
                    <h5>Live Chat</h5>
                    <p class="text-muted mb-0">Chat with our support team</p>
                    <small class="text-success">Available 24/7</small>
                </div>
                
                <div class="contact-card" onclick="callSupport()">
                    <div class="contact-icon">
                        <i class="fas fa-phone"></i>
                    </div>
                    <h5>Call Us</h5>
                    <p class="text-muted mb-0">+1 (800) ORDIVO-1</p>
                    <small class="text-success">Mon-Sun 8AM-10PM</small>
                </div>
                
                <div class="contact-card" onclick="emailSupport()">
                    <div class="contact-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <h5>Email Support</h5>
                    <p class="text-muted mb-0">support@ordivo.com</p>
                    <small class="text-muted">Response within 24 hours</small>
                </div>
            </div>
        </div>

        <!-- FAQ Section -->
        <div class="help-card">
            <h3 class="section-title">
                <i class="fas fa-question"></i>Frequently Asked Questions
            </h3>
            
            <div class="faq-list">
                <?php foreach ($faqs as $index => $faq): ?>
                    <div class="faq-item">
                        <button class="faq-question" onclick="toggleFaq(<?= $index ?>)">
                            <span><?= htmlspecialchars($faq['question']) ?></span>
                            <i class="fas fa-chevron-down" id="faq-icon-<?= $index ?>"></i>
                        </button>
                        <div class="faq-answer" id="faq-answer-<?= $index ?>">
                            <p class="mb-0"><?= htmlspecialchars($faq['answer']) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="help-card">
            <h3 class="section-title">
                <i class="fas fa-bolt"></i>Quick Actions
            </h3>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <button class="btn btn-outline-primary w-100" onclick="reportIssue()">
                        <i class="fas fa-exclamation-triangle me-2"></i>Report an Issue
                    </button>
                </div>
                <div class="col-md-6 mb-3">
                    <button class="btn btn-outline-primary w-100" onclick="requestRefund()">
                        <i class="fas fa-undo me-2"></i>Request Refund
                    </button>
                </div>
                <div class="col-md-6 mb-3">
                    <button class="btn btn-outline-primary w-100" onclick="trackOrder()">
                        <i class="fas fa-map-marker-alt me-2"></i>Track My Order
                    </button>
                </div>
                <div class="col-md-6 mb-3">
                    <button class="btn btn-outline-primary w-100" onclick="updateProfile()">
                        <i class="fas fa-user-edit me-2"></i>Update Profile
                    </button>
                </div>
            </div>
        </div>

        <!-- Feedback -->
        <div class="help-card">
            <h3 class="section-title">
                <i class="fas fa-star"></i>Feedback
            </h3>
            
            <p class="text-muted mb-3">Help us improve ORDIVO by sharing your feedback</p>
            
            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-primary" onclick="giveFeedback()">
                    <i class="fas fa-comment me-2"></i>Give Feedback
                </button>
                <button class="btn btn-outline-primary" onclick="rateApp()">
                    <i class="fas fa-star me-2"></i>Rate Our App
                </button>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function toggleFaq(index) {
            const answer = document.getElementById(`faq-answer-${index}`);
            const icon = document.getElementById(`faq-icon-${index}`);
            
            if (answer.classList.contains('show')) {
                answer.classList.remove('show');
                icon.style.transform = 'rotate(0deg)';
            } else {
                // Close all other FAQs
                document.querySelectorAll('.faq-answer').forEach(el => el.classList.remove('show'));
                document.querySelectorAll('[id^="faq-icon-"]').forEach(el => el.style.transform = 'rotate(0deg)');
                
                // Open clicked FAQ
                answer.classList.add('show');
                icon.style.transform = 'rotate(180deg)';
            }
        }

        function openChat() {
            alert('Live chat functionality would be implemented here');
        }

        function callSupport() {
            window.location.href = 'tel:+18006734486';
        }

        function emailSupport() {
            window.location.href = 'mailto:support@ordivo.com';
        }

        function reportIssue() {
            alert('Issue reporting functionality would be implemented here');
        }

        function requestRefund() {
            alert('Refund request functionality would be implemented here');
        }

        function trackOrder() {
            window.location.href = 'orders.php';
        }

        function updateProfile() {
            window.location.href = 'profile.php';
        }

        function giveFeedback() {
            alert('Feedback form would be implemented here');
        }

        function rateApp() {
            alert('App rating functionality would be implemented here');
        }
    </script>
</body>
</html>