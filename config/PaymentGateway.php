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
        // Demo mode - simulate successful payment for testing
        if (PAYMENT_ENVIRONMENT === 'sandbox' && (empty(BKASH_APP_KEY) || BKASH_APP_KEY === 'your_bkash_app_key')) {
            $transactionId = 'BKASH-DEMO-' . time();
            
            return [
                'success' => true,
                'payment_url' => '../customer/payment_success.php?order_id=' . $this->orderId . '&method=bkash&demo=1',
                'redirect_url' => '../customer/payment_success.php?order_id=' . $this->orderId . '&method=bkash&demo=1',
                'transaction_id' => $transactionId,
                'message' => 'Demo mode: Payment simulated successfully'
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
 * Payment Gateway Factory
 */
class PaymentGatewayFactory {
    public static function create($method, $orderId, $amount, $customerInfo) {
        switch ($method) {
            case 'bkash':
                return new BkashGateway($orderId, $amount, $customerInfo);
            case 'cash':
                return new CashOnDeliveryGateway($orderId, $amount, $customerInfo);
            case 'wallet':
                return new WalletGateway($orderId, $amount, $customerInfo);
            // Add more gateways as needed
            default:
                throw new Exception('Unsupported payment method');
        }
    }
}
?>
