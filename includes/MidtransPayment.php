<?php
/**
 * Midtrans Payment Integration Class
 * Handles payment processing with Midtrans payment gateway
 */

require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/config.php';

class MidtransPayment {
    private $serverKey;
    private $clientKey;
    private $environment;
    private $merchantId;
    private $isProduction;
    
    // Midtrans API URLs
    private $sandboxUrl = 'https://app.sandbox.midtrans.com';
    private $productionUrl = 'https://app.midtrans.com';
    
    public function __construct() {
        $this->serverKey = EnvLoader::get('MIDTRANS_SERVER_KEY', '');
        $this->clientKey = EnvLoader::get('MIDTRANS_CLIENT_KEY', '');
        $this->environment = EnvLoader::get('MIDTRANS_ENVIRONMENT', 'sandbox');
        $this->merchantId = EnvLoader::get('MIDTRANS_MERCHANT_ID', '');
        $this->isProduction = ($this->environment === 'production');
    }
    
    /**
     * Get the appropriate API URL based on environment
     */
    private function getApiUrl() {
        return $this->isProduction ? $this->productionUrl : $this->sandboxUrl;
    }
    
    /**
     * Create Snap token for payment
     */
    public function createSnapToken($orderData) {
        $orderId = $orderData['order_id'];
        $grossAmount = $orderData['amount'];
        $customerDetails = $orderData['customer_details'];
        $itemDetails = $orderData['items'];
        
        $transactionDetails = [
            'order_id' => $orderId,
            'gross_amount' => $grossAmount
        ];
        
        $payload = [
            'transaction_details' => $transactionDetails,
            'customer_details' => $customerDetails,
            'item_details' => $itemDetails,
            'enabled_payments' => $this->getEnabledPayments()
        ];
        
        // Add expiry time if configured
        $expiryTime = EnvLoader::get('MIDTRANS_TRANSACTION_EXPIRY_TIME', '24');
        if ($expiryTime) {
            $payload['expiry'] = [
                'unit' => 'hours',
                'duration' => (int)$expiryTime
            ];
        }
        
        // Add custom fields if needed
        if (isset($orderData['custom_field1'])) {
            $payload['custom_field1'] = $orderData['custom_field1'];
        }
        
        return $this->requestSnapToken($payload);
    }
    
    /**
     * Get enabled payment methods from .env
     */
    private function getEnabledPayments() {
        $paymentMethods = EnvLoader::get('MIDTRANS_PAYMENT_METHODS', 'credit_card,bank_transfer,ewallet,qris');
        return explode(',', $paymentMethods);
    }
    
    /**
     * Request Snap token from Midtrans API
     */
    private function requestSnapToken($payload) {
        $url = $this->getApiUrl() . '/snap/v1/transactions';
        
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Basic ' . base64_encode($this->serverKey . ':')
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 201) {
            throw new Exception("Failed to create Snap token. HTTP Code: $httpCode, Response: $response");
        }
        
        $result = json_decode($response, true);
        
        if (isset($result['token'])) {
            return $result['token'];
        }
        
        throw new Exception("Invalid response from Midtrans API");
    }
    
    /**
     * Verify payment notification from Midtrans
     */
    public function verifyNotification($notificationData) {
        // Verify signature key
        $orderId = $notificationData['order_id'];
        $statusCode = $notificationData['status_code'];
        $grossAmount = $notificationData['gross_amount'];
        $signatureKey = $notificationData['signature_key'];
        
        $expectedSignature = hash('sha512', $orderId . $statusCode . $grossAmount . $this->serverKey);
        
        if ($signatureKey !== $expectedSignature) {
            throw new Exception("Invalid signature key");
        }
        
        return true;
    }
    
    /**
     * Get transaction status
     */
    public function getTransactionStatus($orderId) {
        $url = $this->getApiUrl() . '/v2/' . $orderId . '/status';
        
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Authorization: Basic ' . base64_encode($this->serverKey . ':')
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception("Failed to get transaction status. HTTP Code: $httpCode");
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Cancel transaction
     */
    public function cancelTransaction($orderId) {
        $url = $this->getApiUrl() . '/v2/' . $orderId . '/cancel';
        
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Authorization: Basic ' . base64_encode($this->serverKey . ':')
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception("Failed to cancel transaction. HTTP Code: $httpCode");
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Get client key for frontend
     */
    public function getClientKey() {
        return $this->clientKey;
    }
    
    /**
     * Get environment status
     */
    public function isProduction() {
        return $this->isProduction;
    }
}
