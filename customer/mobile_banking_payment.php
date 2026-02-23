<?php
/**
 * ORDIVO - Mobile Banking Payment Page
 * Simulates real mobile banking payment flow (bKash, Nagad, Rocket, Upay)
 */

require_once '../config/db_connection.php';

// Get payment details from URL
$orderId = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
$method = isset($_GET['method']) ? sanitizeInput($_GET['method']) : '';
$amount = isset($_GET['amount']) ? floatval($_GET['amount']) : 0;

if (!$orderId || !$method || !$amount) {
    header('Location: checkout.php');
    exit;
}

// Get order details
$order = fetchRow("SELECT * FROM orders WHERE id = ?", [$orderId]);
if (!$order) {
    header('Location: checkout.php');
    exit;
}

// Payment method details
$paymentMethods = [
    'bkash' => [
        'name' => 'bKash',
        'color' => '#E2136E',
        'logo' => 'https://cdn.iconscout.com/icon/free/png-256/bkash-3-569288.png'
    ],
    'nagad' => [
        'name' => 'Nagad',
        'color' => '#F15D22',
        'logo' => 'https://seeklogo.com/images/N/nagad-logo-7A70CCFEE0-seeklogo.com.png'
    ],
    'rocket' => [
        'name' => 'Rocket',
        'color' => '#8B3A8B',
        'logo' => 'https://seeklogo.com/images/D/dutch-bangla-rocket-logo-B4D1CC458D-seeklogo.com.png'
    ],
    'upay' => [
        'name' => 'Upay',
        'color' => '#FF6B00',
        'logo' => 'https://upay.com.bd/wp-content/uploads/2021/03/upay-logo.png'
    ]
];

$paymentInfo = $paymentMethods[$method] ?? $paymentMethods['bkash'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $paymentInfo['name'] ?> Payment - ORDIVO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --payment-color: <?= $paymentInfo['color'] ?>;
        }
        
        body {
            background: linear-gradient(135deg, var(--payment-color) 0%, #ffffff 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .payment-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 450px;
            width: 100%;
            overflow: hidden;
        }
        
        .payment-header {
            background: var(--payment-color);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .payment-logo {
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 50%;
            margin: 0 auto 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px;
        }
        
        .payment-logo img {
            max-width: 100%;
            max-height: 100%;
        }
        
        .payment-body {
            padding: 2rem;
        }
        
        .step {
            display: none;
        }
        
        .step.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 0.75rem 1rem;
            font-size: 1.1rem;
        }
        
        .form-control:focus {
            border-color: var(--payment-color);
            box-shadow: 0 0 0 0.2rem rgba(226, 19, 110, 0.25);
        }
        
        .btn-payment {
            background: var(--payment-color);
            border: none;
            border-radius: 10px;
            padding: 0.75rem 2rem;
            font-size: 1.1rem;
            font-weight: 600;
            color: white;
            width: 100%;
            transition: all 0.3s ease;
        }
        
        .btn-payment:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            color: white;
        }
        
        .btn-payment:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .otp-inputs {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin: 1.5rem 0;
        }
        
        .otp-input {
            width: 50px;
            height: 60px;
            text-align: center;
            font-size: 1.5rem;
            font-weight: bold;
            border: 2px solid #e9ecef;
            border-radius: 10px;
        }
        
        .otp-input:focus {
            border-color: var(--payment-color);
            outline: none;
        }
        
        .pin-inputs {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin: 1.5rem 0;
        }
        
        .pin-input {
            width: 50px;
            height: 60px;
            text-align: center;
            font-size: 2rem;
            font-weight: bold;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            -webkit-text-security: disc;
        }
        
        .pin-input:focus {
            border-color: var(--payment-color);
            outline: none;
        }
        
        .amount-display {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            text-align: center;
            margin-bottom: 1.5rem;
        }
        
        .amount-display .label {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        
        .amount-display .amount {
            font-size: 2rem;
            font-weight: bold;
            color: var(--payment-color);
        }
        
        .timer {
            text-align: center;
            color: #6c757d;
            margin-top: 1rem;
        }
        
        .success-icon {
            width: 80px;
            height: 80px;
            background: #28a745;
            border-radius: 50%;
            margin: 0 auto 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2.5rem;
        }
        
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <!-- Header -->
        <div class="payment-header">
            <div class="payment-logo">
                <img src="<?= $paymentInfo['logo'] ?>" alt="<?= $paymentInfo['name'] ?>" onerror="this.style.display='none'">
            </div>
            <h3><?= $paymentInfo['name'] ?> Payment</h3>
            <p class="mb-0">Secure Payment Gateway</p>
        </div>
        
        <!-- Body -->
        <div class="payment-body">
            <!-- Amount Display -->
            <div class="amount-display">
                <div class="label">Amount to Pay</div>
                <div class="amount">৳<?= number_format($amount, 2) ?></div>
            </div>
            
            <!-- Step 1: Mobile Number -->
            <div class="step active" id="step1">
                <h5 class="mb-3">Enter Your <?= $paymentInfo['name'] ?> Number</h5>
                <form id="mobileForm">
                    <div class="mb-3">
                        <input type="tel" class="form-control" id="mobileNumber" placeholder="01XXXXXXXXX" maxlength="11" required>
                        <small class="text-muted">Enter your 11-digit mobile number</small>
                    </div>
                    <button type="submit" class="btn btn-payment">
                        <span id="step1BtnText">Continue</span>
                        <span id="step1Spinner" class="loading-spinner d-none"></span>
                    </button>
                </form>
            </div>
            
            <!-- Step 2: OTP Verification -->
            <div class="step" id="step2">
                <h5 class="mb-3">Enter OTP</h5>
                <p class="text-muted">We've sent a 6-digit code to <strong id="displayMobile"></strong></p>
                <form id="otpForm">
                    <div class="otp-inputs">
                        <input type="text" class="otp-input" maxlength="1" data-index="0">
                        <input type="text" class="otp-input" maxlength="1" data-index="1">
                        <input type="text" class="otp-input" maxlength="1" data-index="2">
                        <input type="text" class="otp-input" maxlength="1" data-index="3">
                        <input type="text" class="otp-input" maxlength="1" data-index="4">
                        <input type="text" class="otp-input" maxlength="1" data-index="5">
                    </div>
                    <div class="timer">
                        <small>Resend OTP in <span id="otpTimer">60</span>s</small>
                    </div>
                    <button type="submit" class="btn btn-payment mt-3">
                        <span id="step2BtnText">Verify OTP</span>
                        <span id="step2Spinner" class="loading-spinner d-none"></span>
                    </button>
                </form>
            </div>
            
            <!-- Step 3: PIN Entry -->
            <div class="step" id="step3">
                <h5 class="mb-3">Enter Your PIN</h5>
                <p class="text-muted">Enter your <?= $paymentInfo['name'] ?> PIN to confirm payment</p>
                <form id="pinForm">
                    <div class="pin-inputs">
                        <input type="password" class="pin-input" maxlength="1" data-index="0" inputmode="numeric">
                        <input type="password" class="pin-input" maxlength="1" data-index="1" inputmode="numeric">
                        <input type="password" class="pin-input" maxlength="1" data-index="2" inputmode="numeric">
                        <input type="password" class="pin-input" maxlength="1" data-index="3" inputmode="numeric">
                        <input type="password" class="pin-input" maxlength="1" data-index="4" inputmode="numeric">
                    </div>
                    <button type="submit" class="btn btn-payment mt-3">
                        <span id="step3BtnText">Confirm Payment</span>
                        <span id="step3Spinner" class="loading-spinner d-none"></span>
                    </button>
                </form>
            </div>
            
            <!-- Step 4: Processing -->
            <div class="step" id="step4">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <h5>Processing Payment...</h5>
                    <p class="text-muted">Please wait while we process your payment</p>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        const orderId = <?= $orderId ?>;
        const method = '<?= $method ?>';
        const amount = <?= $amount ?>;
        let mobileNumber = '';
        let otpTimerInterval;
        
        // Step 1: Mobile Number
        document.getElementById('mobileForm').addEventListener('submit', function(e) {
            e.preventDefault();
            mobileNumber = document.getElementById('mobileNumber').value;
            
            if (mobileNumber.length !== 11 || !mobileNumber.startsWith('01')) {
                alert('Please enter a valid 11-digit mobile number starting with 01');
                return;
            }
            
            // Show loading
            document.getElementById('step1BtnText').classList.add('d-none');
            document.getElementById('step1Spinner').classList.remove('d-none');
            
            // Simulate API call
            setTimeout(() => {
                document.getElementById('displayMobile').textContent = mobileNumber;
                showStep(2);
                startOTPTimer();
            }, 1500);
        });
        
        // OTP Input handling
        const otpInputs = document.querySelectorAll('.otp-input');
        otpInputs.forEach((input, index) => {
            input.addEventListener('input', function() {
                if (this.value.length === 1 && index < otpInputs.length - 1) {
                    otpInputs[index + 1].focus();
                }
            });
            
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Backspace' && this.value === '' && index > 0) {
                    otpInputs[index - 1].focus();
                }
            });
        });
        
        // Step 2: OTP Verification
        document.getElementById('otpForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const otp = Array.from(otpInputs).map(input => input.value).join('');
            
            if (otp.length !== 6) {
                alert('Please enter complete OTP');
                return;
            }
            
            // Show loading
            document.getElementById('step2BtnText').classList.add('d-none');
            document.getElementById('step2Spinner').classList.remove('d-none');
            
            // Simulate API call
            setTimeout(() => {
                clearInterval(otpTimerInterval);
                showStep(3);
            }, 1500);
        });
        
        // PIN Input handling
        const pinInputs = document.querySelectorAll('.pin-input');
        pinInputs.forEach((input, index) => {
            input.addEventListener('input', function() {
                if (this.value.length === 1 && index < pinInputs.length - 1) {
                    pinInputs[index + 1].focus();
                }
            });
            
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Backspace' && this.value === '' && index > 0) {
                    pinInputs[index - 1].focus();
                }
            });
        });
        
        // Step 3: PIN Confirmation
        document.getElementById('pinForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const pin = Array.from(pinInputs).map(input => input.value).join('');
            
            if (pin.length !== 5) {
                alert('Please enter complete PIN');
                return;
            }
            
            // Show loading
            document.getElementById('step3BtnText').classList.add('d-none');
            document.getElementById('step3Spinner').classList.remove('d-none');
            
            // Show processing
            showStep(4);
            
            // Simulate payment processing
            setTimeout(() => {
                // Redirect to success page
                window.location.href = `payment_success.php?order_id=${orderId}&method=${method}`;
            }, 2000);
        });
        
        function showStep(stepNumber) {
            document.querySelectorAll('.step').forEach(step => step.classList.remove('active'));
            document.getElementById('step' + stepNumber).classList.add('active');
            
            // Reset loading states
            document.querySelectorAll('[id$="BtnText"]').forEach(el => el.classList.remove('d-none'));
            document.querySelectorAll('[id$="Spinner"]').forEach(el => el.classList.add('d-none'));
            
            // Focus first input
            if (stepNumber === 2) {
                otpInputs[0].focus();
            } else if (stepNumber === 3) {
                pinInputs[0].focus();
            }
        }
        
        function startOTPTimer() {
            let timeLeft = 60;
            const timerElement = document.getElementById('otpTimer');
            
            otpTimerInterval = setInterval(() => {
                timeLeft--;
                timerElement.textContent = timeLeft;
                
                if (timeLeft <= 0) {
                    clearInterval(otpTimerInterval);
                    timerElement.parentElement.innerHTML = '<a href="#" onclick="resendOTP(); return false;">Resend OTP</a>';
                }
            }, 1000);
        }
        
        function resendOTP() {
            alert('OTP resent to ' + mobileNumber);
            startOTPTimer();
        }
    </script>
</body>
</html>
