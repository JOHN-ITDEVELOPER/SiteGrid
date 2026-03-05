<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\MpesaTransaction;
use App\Models\OwnerWallet;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MpesaService
{
    protected $consumerKey;
    protected $consumerSecret;
    protected $passkey;
    protected $shortcode;
    protected $environment;
    protected $b2cShortcode;
    protected $b2cInitiatorName;
    protected $b2cSecurityCredential;

    protected $requestTimeout;
    protected $connectTimeout;
    protected $retryTimes;
    protected $retrySleepMs;

    public function __construct()
    {
        $this->consumerKey = config('services.mpesa.consumer_key');
        $this->consumerSecret = config('services.mpesa.consumer_secret');
        $this->passkey = config('services.mpesa.passkey');
        $this->shortcode = config('services.mpesa.shortcode');
        $this->b2cShortcode = config('services.mpesa.b2c_shortcode');
        $this->b2cInitiatorName = config('services.mpesa.b2c_initiator_name');
        $this->b2cSecurityCredential = config('services.mpesa.b2c_security_credential');
        $this->environment = config('services.mpesa.environment', 'sandbox');
        $this->requestTimeout = (int) config('services.mpesa.timeout', 60);
        $this->connectTimeout = (int) config('services.mpesa.connect_timeout', 15);
        $this->retryTimes = (int) config('services.mpesa.retry_times', 1);
        $this->retrySleepMs = (int) config('services.mpesa.retry_sleep_ms', 500);
    }

    /**
     * Get OAuth access token from M-Pesa API.
     */
    protected function getAccessToken()
    {
        $url = $this->environment === 'production'
            ? 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials'
            : 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';

        try {
            $response = Http::withBasicAuth($this->consumerKey, $this->consumerSecret)
                ->timeout($this->requestTimeout)
                ->connectTimeout($this->connectTimeout)
                ->retry($this->retryTimes, $this->retrySleepMs)
                ->get($url);

            if ($response->successful()) {
                return $response->json()['access_token'];
            }

            Log::error('M-Pesa OAuth failed', ['response' => $response->body()]);
            return null;
        } catch (\Exception $e) {
            Log::error('M-Pesa OAuth exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Get base URL for M-Pesa API.
     */
    protected function getBaseUrl()
    {
        return $this->environment === 'production'
            ? 'https://api.safaricom.co.ke'
            : 'https://sandbox.safaricom.co.ke';
    }

    /**
     * Initiate STK Push.
     *
     * @param string $phoneNumber Phone number in format 2547XXXXXXXX
     * @param float $amount Amount to charge
     * @param int|null $walletId Wallet ID to credit for top-ups
     * @param array $options Optional overrides:
     *  - related_model (default: OwnerWallet::class)
     *  - related_id (default: walletId)
     *  - account_reference (default: Wallet-{walletId})
     *  - transaction_desc (default: SiteGrid Wallet Top-up)
     * @return array
     */
    public function stkPush($phoneNumber, $amount, $walletId = null, array $options = [])
    {
        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            return ['success' => false, 'message' => 'Failed to get access token'];
        }

        $timestamp = date('YmdHis');
        $password = base64_encode($this->shortcode . $this->passkey . $timestamp);
        $callbackUrl = config('app.url') . '/mjengo/public/api/mpesa/callback/stk';

        $relatedModel = $options['related_model'] ?? OwnerWallet::class;
        $relatedId = $options['related_id'] ?? $walletId;
        $accountReference = $options['account_reference'] ?? ('Wallet-' . ($walletId ?? 'NA'));
        $transactionDesc = $options['transaction_desc'] ?? 'SiteGrid Wallet Top-up';

        Log::info('STK Push Callback URL: ' . $callbackUrl);

        $payload = [
            'BusinessShortCode' => $this->shortcode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => 'CustomerPayBillOnline',
            'Amount' => (int) $amount,
            'PartyA' => $phoneNumber,
            'PartyB' => $this->shortcode,
            'PhoneNumber' => $phoneNumber,
            'CallBackURL' => $callbackUrl,
            'AccountReference' => $accountReference,
            'TransactionDesc' => $transactionDesc,
        ];

        try {
            $url = $this->getBaseUrl() . '/mpesa/stkpush/v1/processrequest';
            $response = Http::withToken($accessToken)
                ->timeout($this->requestTimeout)
                ->connectTimeout($this->connectTimeout)
                ->retry($this->retryTimes, $this->retrySleepMs)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post($url, $payload);

            $responseData = $response->json();
            if (!is_array($responseData)) {
                $responseData = [];
            }

            if ($response->successful() && isset($responseData['CheckoutRequestID'])) {
                $transaction = MpesaTransaction::create([
                    'transaction_type' => 'stk_push',
                    'merchant_request_id' => $responseData['MerchantRequestID'] ?? null,
                    'checkout_request_id' => $responseData['CheckoutRequestID'],
                    'phone_number' => $phoneNumber,
                    'amount' => $amount,
                    'status' => 'pending',
                    'related_model' => $relatedModel,
                    'related_id' => $relatedId,
                    'raw_response' => $responseData,
                ]);

                return [
                    'success' => true,
                    'message' => $responseData['CustomerMessage'] ?? 'STK Push sent successfully',
                    'transaction_id' => $transaction->id,
                    'checkout_request_id' => $responseData['CheckoutRequestID'],
                ];
            }

            $responseBody = trim((string) $response->body());
            $responseBodySnippet = mb_substr($responseBody, 0, 500);

            Log::error('M-Pesa STK Push failed', [
                'status' => $response->status(),
                'reason' => $response->reason(),
                'response_json' => $responseData,
                'response_body' => $responseBodySnippet,
                'request_url' => $url,
                'callback_url' => $callbackUrl,
                'phone' => $phoneNumber,
                'amount' => (int) $amount,
                'shortcode' => $this->shortcode,
            ]);

            // Determine user-friendly message based on error type
            if ($response->status() >= 500 && $response->status() < 600) {
                // 5xx errors = Safaricom service issue
                $message = 'M-Pesa service is temporarily unavailable. Please try again in a few minutes or pay via: Paybill 522533, Account: INV-' . str_pad((string) ($options['related_id'] ?? 'XXXXX'), 8, '0', STR_PAD_LEFT);
            } elseif ($response->status() === 401 || $response->status() === 403) {
                // Authentication/authorization issues
                $message = 'Payment gateway configuration error. Please contact support.';
            } else {
                // Other errors - try to get specific message from response
                $message = $responseData['errorMessage']
                    ?? $responseData['ResponseDescription']
                    ?? ($responseBodySnippet !== '' ? $responseBodySnippet : null)
                    ?? ('Failed to initiate payment (HTTP ' . $response->status() . '). Please try again.');
            }

            return [
                'success' => false,
                'message' => $message,
            ];
        } catch (\Exception $e) {
            Log::error('M-Pesa STK Push exception', [
                'error' => $e->getMessage(),
                'callback_url' => $callbackUrl,
                'phone' => $phoneNumber,
                'amount' => (int) $amount,
                'shortcode' => $this->shortcode,
            ]);
            return [
                'success' => false,
                'message' => 'An error occurred while processing your request',
            ];
        }
    }

    /**
     * Initiate STK Push for invoice payment.
     */
    public function stkPushInvoice(string $phoneNumber, Invoice $invoice): array
    {
        $invoiceNumber = 'INV-' . str_pad((string) $invoice->id, 5, '0', STR_PAD_LEFT);

        return $this->stkPush($phoneNumber, (float) $invoice->amount, null, [
            'related_model' => Invoice::class,
            'related_id' => $invoice->id,
            'account_reference' => $invoiceNumber,
            'transaction_desc' => 'SiteGrid Invoice Payment',
        ]);
    }

    /**
     * Send money to customer (B2C) for worker withdrawals.
     *
     * @param string $phoneNumber Phone number in format 2547XXXXXXXX
     * @param float $amount Amount to send
     * @param int $payoutId Payout or WorkerClaim ID
     * @param string $relatedModel Model class name
     * @return array
     */
    public function b2c($phoneNumber, $amount, $payoutId, $relatedModel = 'App\\Models\\Payout')
    {
        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            return ['success' => false, 'message' => 'Failed to get access token'];
        }

        $callbackUrl = config('app.url') . '/mjengo/public/api/mpesa/callback/b2c';

        $payload = [
            'InitiatorName' => $this->b2cInitiatorName,
            'SecurityCredential' => $this->b2cSecurityCredential,
            'CommandID' => 'BusinessPayment', // For business payments
            'Amount' => (int) $amount,
            'PartyA' => $this->b2cShortcode,
            'PartyB' => $phoneNumber,
            'Remarks' => 'SiteGrid Worker Withdrawal',
            'QueueTimeOutURL' => $callbackUrl,
            'ResultURL' => $callbackUrl,
            'Occasion' => 'Withdrawal-' . $payoutId,
        ];

        try {
            $url = $this->getBaseUrl() . '/mpesa/b2c/v1/paymentrequest';
            $response = Http::withToken($accessToken)
                ->timeout($this->requestTimeout)
                ->connectTimeout($this->connectTimeout)
                ->retry($this->retryTimes, $this->retrySleepMs)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($url, $payload);

            $responseData = $response->json();

            if ($response->successful() && isset($responseData['ConversationID'])) {
                // Create pending transaction record
                $transaction = MpesaTransaction::create([
                    'transaction_type' => 'b2c',
                    'conversation_id' => $responseData['ConversationID'],
                    'originator_conversation_id' => $responseData['OriginatorConversationID'],
                    'phone_number' => $phoneNumber,
                    'amount' => $amount,
                    'status' => 'pending',
                    'related_model' => $relatedModel,
                    'related_id' => $payoutId,
                    'raw_response' => $responseData,
                ]);

                return [
                    'success' => true,
                    'message' => 'Payment initiated successfully',
                    'transaction_id' => $transaction->id,
                    'conversation_id' => $responseData['ConversationID'],
                ];
            }

            Log::error('M-Pesa B2C failed', ['response' => $responseData]);
            return [
                'success' => false,
                'message' => $responseData['errorMessage'] ?? 'Failed to send payment',
            ];
        } catch (\Exception $e) {
            Log::error('M-Pesa B2C exception', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'An error occurred while processing payment',
            ];
        }
    }

    /**
     * Query STK Push transaction status.
     *
     * @param string $checkoutRequestId
     * @return array
     */
    public function stkQuery($checkoutRequestId)
    {
        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            return ['success' => false, 'message' => 'Failed to get access token'];
        }

        $timestamp = date('YmdHis');
        $password = base64_encode($this->shortcode . $this->passkey . $timestamp);

        $payload = [
            'BusinessShortCode' => $this->shortcode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'CheckoutRequestID' => $checkoutRequestId,
        ];

        try {
            $url = $this->getBaseUrl() . '/mpesa/stkpushquery/v1/query';
            $response = Http::withToken($accessToken)
                ->timeout($this->requestTimeout)
                ->connectTimeout($this->connectTimeout)
                ->retry($this->retryTimes, $this->retrySleepMs)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($url, $payload);

            return [
                'success' => $response->successful(),
                'data' => $response->json(),
            ];
        } catch (\Exception $e) {
            Log::error('M-Pesa STK Query exception', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Query failed'];
        }
    }

    /**
     * Validate owner-provided M-Pesa credentials by attempting token generation.
     * This verifies the consumer_key and consumer_secret are valid before allowing use.
     *
     * @param string $consumerKey Owner's M-Pesa consumer key
     * @param string $consumerSecret Owner's M-Pesa consumer secret
     * @return array ['valid' => bool, 'message' => string, 'token' => string|null]
     */
    public function validateCredentials($consumerKey, $consumerSecret)
    {
        if (empty($consumerKey) || empty($consumerSecret)) {
            return [
                'valid' => false,
                'message' => 'Consumer key and secret are required',
                'token' => null,
            ];
        }

        $url = $this->environment === 'production'
            ? 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials'
            : 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';

        try {
            $response = Http::withBasicAuth($consumerKey, $consumerSecret)
                ->timeout($this->requestTimeout)
                ->connectTimeout($this->connectTimeout)
                ->retry($this->retryTimes, $this->retrySleepMs)
                ->get($url);

            if ($response->successful()) {
                $token = $response->json()['access_token'] ?? null;
                return [
                    'valid' => true,
                    'message' => 'Credentials validated successfully',
                    'token' => $token,
                ];
            }

            $errorBody = $response->json();
            Log::warning('M-Pesa credential validation failed', [
                'status' => $response->status(),
                'error' => $errorBody['error_description'] ?? $errorBody['error'] ?? 'Unknown error',
            ]);

            return [
                'valid' => false,
                'message' => $errorBody['error_description'] ?? 'Invalid credentials. Check consumer key and secret.',
                'token' => null,
            ];
        } catch (\Exception $e) {
            Log::error('M-Pesa credential validation exception', ['error' => $e->getMessage()]);
            return [
                'valid' => false,
                'message' => 'Validation request failed: ' . $e->getMessage(),
                'token' => null,
            ];
        }
    }
}
