<?php
// Payment Gateway Credentials
define('PAYMENT_ENVIRONMENT', 'sandbox'); // 'sandbox' or 'production'

// bKash Configuration
define('BKASH_APP_KEY', 'your_bkash_app_key');
define('BKASH_APP_SECRET', 'your_bkash_app_secret');
define('BKASH_USERNAME', 'your_bkash_username');
define('BKASH_PASSWORD', 'your_bkash_password');
define('BKASH_BASE_URL', PAYMENT_ENVIRONMENT === 'production' 
    ? 'https://checkout.pay.bka.sh/v1.2.0-beta' 
    : 'https://checkout.sandbox.bka.sh/v1.2.0-beta');

// Nagad Configuration
define('NAGAD_MERCHANT_ID', 'your_nagad_merchant_id');
define('NAGAD_MERCHANT_NUMBER', 'your_nagad_merchant_number');
define('NAGAD_PUBLIC_KEY', 'your_nagad_public_key');
define('NAGAD_PRIVATE_KEY', 'your_nagad_private_key');
define('NAGAD_BASE_URL', PAYMENT_ENVIRONMENT === 'production'
    ? 'https://api.mynagad.com'
    : 'http://sandbox.mynagad.com:10080/remote-payment-gateway-1.0');

// SSL Commerz Configuration (for Card payments)
define('SSLCOMMERZ_STORE_ID', 'your_store_id');
define('SSLCOMMERZ_STORE_PASSWORD', 'your_store_password');
define('SSLCOMMERZ_BASE_URL', PAYMENT_ENVIRONMENT === 'production'
    ? 'https://securepay.sslcommerz.com'
    : 'https://sandbox.sslcommerz.com');

// Rocket Configuration (via SSL Commerz)
define('ROCKET_ENABLED', true);

// Upay Configuration
define('UPAY_MERCHANT_ID', 'your_upay_merchant_id');
define('UPAY_MERCHANT_KEY', 'your_upay_merchant_key');
define('UPAY_BASE_URL', PAYMENT_ENVIRONMENT === 'production'
    ? 'https://upay.com.bd/api'
    : 'https://sandbox.upay.com.bd/api');

// Payment Methods Configuration
$paymentMethods = [
    'cash' => [
        'name' => 'Cash on Delivery',
        'icon' => 'fas fa-money-bill-wave',
        'enabled' => true,
        'fee' => 0,
        'description' => 'Pay with cash when your order is delivered'
    ],
    'bkash' => [
        'name' => 'bKash',
        'icon' => 'fab fa-bitcoin', // Use custom icon in production
        'enabled' => true,
        'fee' => 0,
        'description' => 'Pay securely with bKash mobile wallet',
        'logo' => '../assets/images/payment/bkash.png'
    ],
    'nagad' => [
        'name' => 'Nagad',
        'icon' => 'fas fa-mobile-alt',
        'enabled' => true,
        'fee' => 0,
        'description' => 'Pay securely with Nagad mobile wallet',
        'logo' => '../assets/images/payment/nagad.png'
    ],
    'rocket' => [
        'name' => 'Rocket',
        'icon' => 'fas fa-rocket',
        'enabled' => true,
        'fee' => 0,
        'description' => 'Pay securely with Rocket mobile wallet',
        'logo' => '../assets/images/payment/rocket.png'
    ],
    'upay' => [
        'name' => 'Upay',
        'icon' => 'fas fa-wallet',
        'enabled' => true,
        'fee' => 0,
        'description' => 'Pay securely with Upay',
        'logo' => '../assets/images/payment/upay.png'
    ],
    'card' => [
        'name' => 'Credit/Debit Card',
        'icon' => 'fas fa-credit-card',
        'enabled' => true,
        'fee' => 0,
        'description' => 'Pay with Visa, Mastercard, or Amex',
        'logo' => '../assets/images/payment/cards.png'
    ],
    'wallet' => [
        'name' => 'ORDIVO Wallet',
        'icon' => 'fas fa-wallet',
        'enabled' => true,
        'fee' => 0,
        'description' => 'Pay using your ORDIVO wallet balance'
    ]
];

// Payment URLs
define('PAYMENT_SUCCESS_URL', APP_URL . 'customer/payment_success.php');
define('PAYMENT_FAIL_URL', APP_URL . 'customer/payment_fail.php');
define('PAYMENT_CANCEL_URL', APP_URL . 'customer/payment_cancel.php');
define('PAYMENT_IPN_URL', APP_URL . 'customer/payment_ipn.php');

// Payment Settings
define('MIN_ORDER_AMOUNT', 50); // Minimum order amount in BDT
define('MAX_ORDER_AMOUNT', 100000); // Maximum order amount in BDT
define('COD_MAX_AMOUNT', 5000); // Maximum COD amount
define('PAYMENT_TIMEOUT', 900); // 15 minutes in seconds

// Currency
define('CURRENCY', 'BDT');
define('CURRENCY_SYMBOL', '৳');

/**
 * Get available payment methods
 */
function getPaymentMethods() {
    global $paymentMethods;
    return array_filter($paymentMethods, function($method) {
        return $method['enabled'];
    });
}

/**
 * Get payment method details
 */
function getPaymentMethod($methodKey) {
    global $paymentMethods;
    return $paymentMethods[$methodKey] ?? null;
}

/**
 * Check if payment method is enabled
 */
function isPaymentMethodEnabled($methodKey) {
    global $paymentMethods;
    return isset($paymentMethods[$methodKey]) && $paymentMethods[$methodKey]['enabled'];
}

/**
 * Calculate payment fee
 */
function calculatePaymentFee($amount, $method) {
    global $paymentMethods;
    if (!isset($paymentMethods[$method])) {
        return 0;
    }
    return $paymentMethods[$method]['fee'];
}
?>
