<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\MpesaTransaction;
use App\Models\OwnerWallet;
use App\Models\Payout;
use Illuminate\Support\Facades\Log;

class MpesaCallbackController extends Controller
{
    /**
     * Handle STK Push callback from M-Pesa.
     */
    public function stkCallback(Request $request)
    {
        Log::info('M-Pesa STK Callback received', $request->all());

        $callbackData = $request->all();
        
        if (!isset($callbackData['Body']['stkCallback'])) {
            return response()->json(['ResultCode' => 1, 'ResultDesc' => 'Invalid callback data']);
        }

        $callback = $callbackData['Body']['stkCallback'];
        $checkoutRequestId = $callback['CheckoutRequestID'];
        $resultCode = $callback['ResultCode'];
        $resultDesc = $callback['ResultDesc'];

        // Find transaction by checkout request ID
        $transaction = MpesaTransaction::where('checkout_request_id', $checkoutRequestId)->first();

        if (!$transaction) {
            Log::error('M-Pesa transaction not found', ['checkout_request_id' => $checkoutRequestId]);
            return response()->json(['ResultCode' => 1, 'ResultDesc' => 'Transaction not found']);
        }

        // Update transaction with callback data
        $transaction->raw_response = array_merge($transaction->raw_response ?? [], $callbackData);

        if ($transaction->status === 'completed') {
            Log::info('Duplicate STK callback ignored', [
                'transaction_id' => $transaction->id,
                'checkout_request_id' => $checkoutRequestId,
            ]);

            return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Success']);
        }

        if ($resultCode == 0) {
            // Payment successful - extract metadata
            $amount = null;
            $mpesaReceipt = null;
            $phoneNumber = null;

            if (isset($callback['CallbackMetadata']['Item'])) {
                foreach ($callback['CallbackMetadata']['Item'] as $item) {
                    if ($item['Name'] === 'Amount') {
                        $amount = $item['Value'];
                    } elseif ($item['Name'] === 'MpesaReceiptNumber') {
                        $mpesaReceipt = $item['Value'];
                    } elseif ($item['Name'] === 'PhoneNumber') {
                        $phoneNumber = $item['Value'];
                    }
                }
            }

            $transaction->markAsCompleted($mpesaReceipt, $resultDesc);
            
            // Credit wallet top-up transactions (deposits)
            if ($transaction->related_model === OwnerWallet::class && $transaction->related_id) {
                $wallet = OwnerWallet::find($transaction->related_id);
                if ($wallet) {
                    // Get primary deposit account from platform settings
                    $settings = \App\Models\PlatformSetting::firstOrCreate([]);
                    $deposit_account_id = $settings->platform_deposit_account_id;
                    $deposit_account = \App\Models\PlatformAccount::find($deposit_account_id);
                    
                    // Credit the wallet
                    $wallet->credit(
                        $amount ?? $transaction->amount,
                        'top_up',
                        $transaction->id,
                        "M-Pesa deposit to wallet - Receipt: {$mpesaReceipt}"
                    );
                    
                    // Update transaction to track which account processed the deposit
                    $transaction->update([
                        'platform_account_id' => $deposit_account_id,
                    ]);
                    
                    Log::info('Deposit processed to wallet', [
                        'wallet_id' => $wallet->id,
                        'amount' => $amount ?? $transaction->amount,
                        'receipt' => $mpesaReceipt,
                        'account_id' => $deposit_account_id,
                        'account_shortcode' => $deposit_account?->shortcode,
                        'transaction_id' => $transaction->id,
                    ]);
                }
            }

            // Reconcile invoice payments → Platform Revenue
            if ($transaction->related_model === Invoice::class && $transaction->related_id) {
                $invoice = Invoice::find($transaction->related_id);
                if ($invoice && $invoice->status !== 'paid') {
                    // Update invoice status
                    $invoice->update([
                        'status' => 'paid',
                        'paid_at' => now(),
                        'notes' => trim(($invoice->notes ? $invoice->notes . ' | ' : '') . "Paid via M-Pesa STK - Receipt: {$mpesaReceipt}"),
                    ]);

                    // Get primary invoice account from platform settings
                    $settings = \App\Models\PlatformSetting::firstOrCreate([]);
                    $invoice_account_id = $settings->platform_invoice_account_id;
                    $invoice_account = \App\Models\PlatformAccount::find($invoice_account_id);

                    // Create platform revenue record
                    \App\Models\PlatformRevenue::create([
                        'invoice_id' => $invoice->id,
                        'mpesa_transaction_id' => $transaction->id,
                        'amount' => $amount ?? $invoice->amount,
                        'currency' => 'KES',
                        'mpesa_receipt' => $mpesaReceipt,
                        'platform_account_id' => $invoice_account_id,
                        'destination_shortcode' => $invoice_account?->shortcode,
                        'status' => 'received',
                        'received_at' => now(),
                        'metadata' => [
                            'phone_number' => $phoneNumber,
                            'transaction_id' => $transaction->id,
                        ],
                    ]);

                    Log::info('Invoice marked as paid + revenue recorded', [
                        'invoice_id' => $invoice->id,
                        'amount' => $amount ?? $invoice->amount,
                        'receipt' => $mpesaReceipt,
                        'transaction_id' => $transaction->id,
                        'account_id' => $invoice_account_id,
                    ]);
                }
            }
        } else {
            // Payment failed
            $transaction->markAsFailed($resultCode, $resultDesc);
            Log::warning('M-Pesa payment failed', [
                'checkout_request_id' => $checkoutRequestId,
                'result_code' => $resultCode,
                'result_desc' => $resultDesc
            ]);
        }

        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Success']);
    }

    /**
     * Handle B2C callback from M-Pesa.
     */
    public function b2cCallback(Request $request)
    {
        Log::info('M-Pesa B2C Callback received', $request->all());

        $callbackData = $request->all();

        if (!isset($callbackData['Result'])) {
            return response()->json(['ResultCode' => 1, 'ResultDesc' => 'Invalid callback data']);
        }

        $result = $callbackData['Result'];
        $conversationId = $result['ConversationID'] ?? null;
        $originatorConversationId = $result['OriginatorConversationID'] ?? null;
        $resultCode = $result['ResultCode'];
        $resultDesc = $result['ResultDesc'];

        // Find transaction by conversation ID
        $transaction = MpesaTransaction::where('conversation_id', $conversationId)
            ->orWhere('originator_conversation_id', $originatorConversationId)
            ->first();

        if (!$transaction) {
            Log::error('M-Pesa B2C transaction not found', [
                'conversation_id' => $conversationId,
                'originator_conversation_id' => $originatorConversationId
            ]);
            return response()->json(['ResultCode' => 1, 'ResultDesc' => 'Transaction not found']);
        }

        // Update transaction with callback data
        $transaction->raw_response = array_merge($transaction->raw_response ?? [], $callbackData);

        if ($resultCode == 0) {
            // Payment successful - extract result parameters
            $mpesaReceipt = null;

            if (isset($result['ResultParameters']['ResultParameter'])) {
                foreach ($result['ResultParameters']['ResultParameter'] as $param) {
                    if ($param['Key'] === 'TransactionReceipt') {
                        $mpesaReceipt = $param['Value'];
                        break;
                    }
                }
            }

            $transaction->markAsCompleted($mpesaReceipt, $resultDesc);
            
            Log::info('B2C payment successful', [
                'transaction_id' => $transaction->id,
                'receipt' => $mpesaReceipt,
                'amount' => $transaction->amount
            ]);

            // Update related Payout status to 'paid'
            if ($transaction->related_model === 'App\\Models\\Payout' && $transaction->related_id) {
                $payout = Payout::find($transaction->related_id);
                if ($payout && $payout->status === 'processing') {
                    $payout->status = 'paid';
                    $payout->error_message = "Paid via M-Pesa - Receipt: {$mpesaReceipt}";
                    $payout->save();
                    
                    Log::info('Payout marked as paid', [
                        'payout_id' => $payout->id,
                        'worker_id' => $payout->worker_id,
                        'amount' => $payout->net_amount
                    ]);
                }
            }
        } else {
            // Payment failed
            $transaction->markAsFailed($resultCode, $resultDesc);
            Log::warning('M-Pesa B2C payment failed', [
                'transaction_id' => $transaction->id,
                'result_code' => $resultCode,
                'result_desc' => $resultDesc
            ]);

            // Update related Payout status to 'failed' and refund wallet
            if ($transaction->related_model === 'App\\Models\\Payout' && $transaction->related_id) {
                $payout = Payout::find($transaction->related_id);
                if ($payout && $payout->status === 'processing') {
                    $payout->status = 'failed';
                    $payout->error_message = "M-Pesa payment failed: {$resultDesc}";
                    $payout->save();
                    
                    // Refund wallet
                    $site = $payout->payCycle->site;
                    $owner = $site->owner;
                    if ($owner->wallet) {
                        $owner->wallet->credit(
                            $payout->net_amount,
                            'refund',
                            $payout->id,
                            "Refund: M-Pesa B2C payment failed - {$resultDesc}"
                        );
                        
                        Log::info('Wallet refunded due to failed B2C', [
                            'payout_id' => $payout->id,
                            'amount' => $payout->net_amount,
                            'wallet_id' => $owner->wallet->id
                        ]);
                    }
                }
            }
        }

        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Success']);
    }
}

