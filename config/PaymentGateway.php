<?php
/**
 * ORDIVO Payment Gateway Handler
 * Abstract class for payment gateway integrations
 */

require_once 'db_connection.php';
require_once 'payment_config.php';

abstract class PaymentGateway {
    protected $db;
    protected $orderId;
    protected $amount;
    protected $currency;
    protected $customerInfo;
    
    public function __construct($orderId, $amount, $customerInfo) {
        global $pdo;
        $this->db = $pdo;
        $this->orderId = $orderId;
        $this->amount = $amount;
        $this->currency = CURRENCY;
        $this->customerInfo = $customerInfo;
    }
    
    abstract public function initiatePayment();
    abstract public function verifyPayment($transactionId);
    abstract public function refundPayment($transactionId, $amount);
    
    /**
     * Create payment transaction record
     */
    protected function createTransaction($paymentMethod, $transactionId = null) {
        $sql = "INSERT INTO payment_transactions 
                (order_id, user_id, payment_method, amount, currency, transaction_id, status) 
                VALUES (?, ?, ?, ?, ?, ?, 'pending')";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $this->orderId,
            $this->customerInfo['user_id'],
            $paymentMethod,
            $this->amount,
            $this->currency,
            $transactionId
        ]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Update transaction status
     */
    protected function updateTransaction($transactionId, $status, $gatewayTransactionId = null, $gatewayResponse = null, $failureReason = null) {
        $sql = "UPDATE payment_transactions 
                SET status = ?, 
                    gateway_transaction_id = ?, 
                    gateway_response = ?, 
                    failure_reason = ?,
                    processed_at = NOW(),
                    updated_at = NOW()
                WHERE transaction_id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $status,
            $gatewayTransactionId,
            $gatewayResponse ? json_encode($gatewayResponse) : null,
            $failureReason,
            $transactionId
        ]);
    }
    
    /**
     * Generate unique transaction ID
     */
    protected function generateTransactionId() {
        return 'TXN' . time() . rand(1000, 9999);
    }
    
    /**
     * Log payment activity
     */
    protected function logActivity($message, $data = []) {
        error_log("Payment Gateway [{$this->orderId}]: " . $message . " - " . json_encode($data));
    }
}

/**
 * bKash Payment Gateway
 */
class BkashGateway extends PaymentGateway {
    private $token;
    
    public function initiatePayment() {
        // Demo mode - redirect to mobile banking payment page for realistic flow
        if (PAYMENT_ENVIRONMENT === 'sandbox' && (empty(BKASH_APP_KEY) || BKASH_APP_KEY === 'your_bkash_app_key')) {
            $transactionId = 'BKASH-DEMO-' . time();
            
            return [
                'success' => true,
                'payment_url' => '../customer/mobile_banking_payment.php?order_id=' . $this->orderId . '&method=bkash&amount=' . $this->amount,
                'redirect_url' => '../customer/mobile_banking_payment.php?order_id=' . $this->orderId . '&method=bkash&amount=' . $this->amount,
                'transaction_id' => $transactionId,
                'message' => 'Redirecting to bKash payment gateway'
            ];
        }
        
        try {
            // Get bKash token
            $this->token = $this->getToken();
            
            if (!$this->token) {
                throw new Exception('Failed to get bKash token');
            }
            
            // Create payment
            $transactionId = $this->generateTransactionId();
            $paymentId = $this->createTransaction('bkash', $transactionId);
            
            $payload = [
                'mode' => '0011',
                'payerReference' => $this->customerInfo['phone'],
                'callbackURL' => PAYMENT_SUCCESS_URL,
                'amount' => $this->amount,
                'currency' => 'BDT',
                'intent' => 'sale',
                'merchantInvoiceNumber' => $transactionId
            ];
            
            $response = $this->makeRequest('/create', $payload, $this->token);
            
            if (isset($response['paymentID'])) {
                $this->updateTransaction($transactionId, 'processing', $response['paymentID'], $response);
                
                return [
                    'success' => true,
                    'payment_url' => BKASH_BASE_URL . '/execute?paymentID=' . $response['paymentID'],
                    'transaction_id' => $transactionId,
                    'payment_id' => $response['paymentID']
                ];
            }
            
            throw new Exception('Failed to create bKash payment');
            
        } catch (Exception $e) {
            $this->logActivity('bKash initiation failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function verifyPayment($paymentID) {
        try {
            $this->token = $this->getToken();
            
            $response = $this->makeRequest('/execute', ['paymentID' => $paymentID], $this->token);
            
            if (isset($response['transactionStatus']) && $response['transactionStatus'] === 'Completed') {
                return [
                    'success' => true,
                    'transaction_id' => $response['trxID'],
                    'amount' => $response['amount'],
                    'data' => $response
                ];
            }
            
            return ['success' => false, 'message' => 'Payment not completed'];
            
        } catch (Exception $e) {
            $this->logActivity('bKash verification failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function refundPayment($transactionId, $amount) {
        try {
            $this->token = $this->getToken();
            
            $payload = [
                'paymentID' => $transactionId,
                'amount' => $amount,
                'trxID' => $this->generateTransactionId(),
                'sku' => 'refund',
                'reason' => 'Customer requested refund'
            ];
            
            $response = $this->makeRequest('/refund', $payload, $this->token);
            
            if (isset($response['transactionStatus']) && $response['transactionStatus'] === 'Completed') {
                return ['success' => true, 'refund_id' => $response['refundTrxID']];
            }
            
            return ['success' => false, 'message' => 'Refund failed'];
            
        } catch (Exception $e) {
            $this->logActivity('bKash refund failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    private function getToken() {
        $url = BKASH_BASE_URL . '/token/grant';
        
        $payload = [
            'app_key' => BKASH_APP_KEY,
            'app_secret' => BKASH_APP_SECRET
        ];
        
        $headers = [
            'Content-Type: application/json',
            'username: ' . BKASH_USERNAME,
            'password: ' . BKASH_PASSWORD
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $data = json_decode($response, true);
        
        return $data['id_token'] ?? null;
    }
    
    private function makeRequest($endpoint, $payload, $token) {
        $url = BKASH_BASE_URL . $endpoint;
        
        $headers = [
            'Content-Type: application/json',
            'Authorization: ' . $token,
            'X-APP-Key: ' . BKASH_APP_KEY
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
}

/**
 * Cash on Delivery Handler
 */
class CashOnDeliveryGateway extends PaymentGateway {
    public function initiatePayment() {
        try {
            // Check COD limit
            if ($this->amount > COD_MAX_AMOUNT) {
                return [
                    'success' => false,
                    'message' => 'Cash on Delivery is not available for orders above ৳' . number_format(COD_MAX_AMOUNT)
                ];
            }
            
            $transactionId = $this->generateTransactionId();
            $this->createTransaction('cash', $transactionId);
            
            return [
                'success' => true,
                'transaction_id' => $transactionId,
                'message' => 'Order placed successfully. Pay cash on delivery.'
            ];
            
        } catch (Exception $e) {
            $this->logActivity('COD initiation failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function verifyPayment($transactionId) {
        // COD doesn't need verification
        return ['success' => true];
    }
    
    public function refundPayment($transactionId, $amount) {
        // COD refunds are handled manually
        return ['success' => true, 'message' => 'Refund will be processed manually'];
    }
}

/**
 * Wallet Payment Handler
 */
class WalletGateway extends PaymentGateway {
    public function initiatePayment() {
        try {
            // Check wallet balance
            $wallet = fetchRow("SELECT balance FROM user_wallets WHERE user_id = ?", [$this->customerInfo['user_id']]);
            
            if (!$wallet || $wallet['balance'] < $this->amount) {
                return [
                    'success' => false,
                    'message' => 'Insufficient wallet balance'
                ];
            }
            
            // Deduct from wallet
            $sql = "UPDATE user_wallets SET balance = balance - ? WHERE user_id = ?";
            executeQuery($sql, [$this->amount, $this->customerInfo['user_id']]);
            
            $transactionId = $this->generateTransactionId();
            $this->createTransaction('wallet', $transactionId);
            $this->updateTransaction($transactionId, 'completed');
            
            return [
                'success' => true,
                'transaction_id' => $transactionId,
                'message' => 'Payment successful from wallet'
            ];
            
        } catch (Exception $e) {
            $this->logActivity('Wallet payment failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function verifyPayment($transactionId) {
        return ['success' => true];
    }
    
    public function refundPayment($transactionId, $amount) {
        try {
            // Add back to wallet
            $sql = "UPDATE user_wallets SET balance = balance + ? WHERE user_id = ?";
            executeQuery($sql, [$amount, $this->customerInfo['user_id']]);
            
            return ['success' => true, 'message' => 'Refund added to wallet'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}

/**
 * Nagad Payment Gateway
 */
class NagadGateway extends PaymentGateway {
    public function initiatePayment() {
        // Demo mode - redirect to mobile banking payment page
        if (PAYMENT_ENVIRONMENT === 'sandbox' && (empty(NAGAD_MERCHANT_ID) || NAGAD_MERCHANT_ID === 'your_nagad_merchant_id')) {
            $transactionId = 'NAGAD-DEMO-' . time();
            
            return [
                'success' => true,
                'payment_url' => '../customer/mobile_banking_payment.php?order_id=' . $this->orderId . '&method=nagad&amount=' . $this->amount,
                'redirect_url' => '../customer/mobile_banking_payment.php?order_id=' . $this->orderId . '&method=nagad&amount=' . $this->amount,
                'transaction_id' => $transactionId,
                'message' => 'Redirecting to Nagad payment gateway'
            ];
        }
        
        // Real Nagad API integration would go here
        return ['success' => false, 'message' => 'Nagad API not configured'];
    }
    
    public function verifyPayment($paymentId) {
        if (PAYMENT_ENVIRONMENT === 'sandbox') {
            return ['success' => true, 'message' => 'Demo payment verified'];
        }
        return ['success' => false, 'message' => 'Nagad API not configured'];
    }
    
    public function refundPayment($transactionId, $amount) {
        if (PAYMENT_ENVIRONMENT === 'sandbox') {
            return ['success' => true, 'message' => 'Demo refund processed'];
        }
        return ['success' => false, 'message' => 'Nagad API not configured'];
    }
}

/**
 * Rocket Payment Gateway
 */
class RocketGateway extends PaymentGateway {
    public function initiatePayment() {
        // Demo mode - redirect to mobile banking payment page
        if (PAYMENT_ENVIRONMENT === 'sandbox' && (empty(SSLCOMMERZ_STORE_ID) || SSLCOMMERZ_STORE_ID === 'your_store_id')) {
            $transactionId = 'ROCKET-DEMO-' . time();
            
            return [
                'success' => true,
                'payment_url' => '../customer/mobile_banking_payment.php?order_id=' . $this->orderId . '&method=rocket&amount=' . $this->amount,
                'redirect_url' => '../customer/mobile_banking_payment.php?order_id=' . $this->orderId . '&method=rocket&amount=' . $this->amount,
                'transaction_id' => $transactionId,
                'message' => 'Redirecting to Rocket payment gateway'
            ];
        }
        
        // Real Rocket API integration (via SSL Commerz) would go here
        return ['success' => false, 'message' => 'Rocket API not configured'];
    }
    
    public function verifyPayment($paymentId) {
        if (PAYMENT_ENVIRONMENT === 'sandbox') {
            return ['success' => true, 'message' => 'Demo payment verified'];
        }
        return ['success' => false, 'message' => 'Rocket API not configured'];
    }
    
    public function refundPayment($transactionId, $amount) {
        if (PAYMENT_ENVIRONMENT === 'sandbox') {
            return ['success' => true, 'message' => 'Demo refund processed'];
        }
        return ['success' => false, 'message' => 'Rocket API not configured'];
    }
}

/**
 * Upay Payment Gateway
 */
class UpayGateway extends PaymentGateway {
    public function initiatePayment() {
        // Demo mode - redirect to mobile banking payment page
        if (PAYMENT_ENVIRONMENT === 'sandbox' && (empty(UPAY_MERCHANT_ID) || UPAY_MERCHANT_ID === 'your_upay_merchant_id')) {
            $transactionId = 'UPAY-DEMO-' . time();
            
            return [
                'success' => true,
                'payment_url' => '../customer/mobile_banking_payment.php?order_id=' . $this->orderId . '&method=upay&amount=' . $this->amount,
                'redirect_url' => '../customer/mobile_banking_payment.php?order_id=' . $this->orderId . '&method=upay&amount=' . $this->amount,
                'transaction_id' => $transactionId,
                'message' => 'Redirecting to Upay payment gateway'
            ];
        }
        
        // Real Upay API integration would go here
        return ['success' => false, 'message' => 'Upay API not configured'];
    }
    
    public function verifyPayment($paymentId) {
        if (PAYMENT_ENVIRONMENT === 'sandbox') {
            return ['success' => true, 'message' => 'Demo payment verified'];
        }
        return ['success' => false, 'message' => 'Upay API not configured'];
    }
    
    public function refundPayment($transactionId, $amount) {
        if (PAYMENT_ENVIRONMENT === 'sandbox') {
            return ['success' => true, 'message' => 'Demo refund processed'];
        }
        return ['success' => false, 'message' => 'Upay API not configured'];
    }
}

/**
 * Card Payment Gateway (via SSL Commerz)
 */
class CardGateway extends PaymentGateway {
    public function initiatePayment() {
        // Demo mode - redirect to mobile banking payment page (simplified for demo)
        if (PAYMENT_ENVIRONMENT === 'sandbox' && (empty(SSLCOMMERZ_STORE_ID) || SSLCOMMERZ_STORE_ID === 'your_store_id')) {
            $transactionId = 'CARD-DEMO-' . time();
            
            return [
                'success' => true,
                'payment_url' => '../customer/mobile_banking_payment.php?order_id=' . $this->orderId . '&method=card&amount=' . $this->amount,
                'redirect_url' => '../customer/mobile_banking_payment.php?order_id=' . $this->orderId . '&method=card&amount=' . $this->amount,
                'transaction_id' => $transactionId,
                'message' => 'Redirecting to card payment gateway'
            ];
        }
        
        // Real SSL Commerz API integration would go here
        return ['success' => false, 'message' => 'Card payment API not configured'];
    }
    
    public function verifyPayment($paymentId) {
        if (PAYMENT_ENVIRONMENT === 'sandbox') {
            return ['success' => true, 'message' => 'Demo payment verified'];
        }
        return ['success' => false, 'message' => 'Card payment API not configured'];
    }
    
    public function refundPayment($transactionId, $amount) {
        if (PAYMENT_ENVIRONMENT === 'sandbox') {
            return ['success' => true, 'message' => 'Demo refund processed'];
        }
        return ['success' => false, 'message' => 'Card payment API not configured'];
    }
}

/**
 * Payment Gateway Factory
 */
class PaymentGatewayFactory {
    public static function create($method, $orderId, $amount, $customerInfo) {
        switch ($method) {
            case 'bkash':
                return new BkashGateway($orderId, $amount, $customerInfo);
            case 'nagad':
                return new NagadGateway($orderId, $amount, $customerInfo);
            case 'rocket':
                return new RocketGateway($orderId, $amount, $customerInfo);
            case 'upay':
                return new UpayGateway($orderId, $amount, $customerInfo);
            case 'card':
                return new CardGateway($orderId, $amount, $customerInfo);
            case 'cash':
                return new CashOnDeliveryGateway($orderId, $amount, $customerInfo);
            case 'wallet':
                return new WalletGateway($orderId, $amount, $customerInfo);
            default:
                throw new Exception('Unsupported payment method');
        }
    }
}
?>

