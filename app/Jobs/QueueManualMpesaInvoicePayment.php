<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Services\MpesaService;
use App\Services\OtpService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class QueueManualMpesaInvoicePayment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private int $invoiceId;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(int $invoiceId)
    {
        $this->invoiceId = $invoiceId;
    }

    public function handle(OtpService $otpService, MpesaService $mpesaService): void
    {
        $invoice = Invoice::with(['site.owner'])->find($this->invoiceId);

        if (!$invoice || $invoice->status === 'paid') {
            return;
        }

        $owner = $invoice->site?->owner;
        if (!$owner) {
            Log::warning('Manual M-Pesa invoice notification skipped: owner missing', [
                'invoice_id' => $this->invoiceId,
            ]);
            return;
        }

        $invoiceNumber = 'INV-' . str_pad((string) $invoice->id, 5, '0', STR_PAD_LEFT);
        $amount = number_format((float) $invoice->amount, 2);
        $siteName = $invoice->site->name;
        $dueDate = $invoice->due_date?->format('d M Y') ?? 'N/A';
        $shortCode = config('services.mpesa.shortcode', 'N/A');
        $accountReference = $invoiceNumber;
        $portalUrl = url('/owner/invoices');

        $normalizedPhone = $this->normalizePhone($owner->phone);
        $stkInitiated = false;

        if ($normalizedPhone && (float) $invoice->amount > 0) {
            $stkResult = $mpesaService->stkPushInvoice($normalizedPhone, $invoice);
            $stkInitiated = (bool) ($stkResult['success'] ?? false);

            if ($stkInitiated) {
                Log::info('Invoice STK push initiated', [
                    'invoice_id' => $invoice->id,
                    'checkout_request_id' => $stkResult['checkout_request_id'] ?? null,
                ]);
            } else {
                Log::warning('Invoice STK push initiation failed', [
                    'invoice_id' => $invoice->id,
                    'message' => $stkResult['message'] ?? 'Unknown error',
                ]);
            }
        }

        $smsMessage = $stkInitiated
            ? "SiteGrid Invoice {$invoiceNumber}: STK prompt sent for KES {$amount}. Complete payment on your phone. Due {$dueDate}. {$portalUrl}"
            : "SiteGrid Invoice {$invoiceNumber}: Pay KES {$amount} for {$siteName} via M-Pesa Paybill {$shortCode}, Account {$accountReference}. Due {$dueDate}. {$portalUrl}";

        if (!empty($owner->phone)) {
            $otpService->sendNotification($owner->phone, $smsMessage);
        }

        if (!empty($owner->email)) {
            Mail::raw(
                "Hello {$owner->name},\n\n"
                . "A new invoice has been generated for {$siteName}.\n"
                . "Invoice: {$invoiceNumber}\n"
                . "Amount: KES {$amount}\n"
                . "Payment method: Manual M-Pesa\n"
                . "Due date: {$dueDate}\n\n"
                . ($stkInitiated
                    ? "An STK push has been sent to your phone.\n"
                    : "Paybill: {$shortCode}\nAccount Reference: {$accountReference}\n")
                . "View invoices: {$portalUrl}\n",
                function ($message) use ($owner, $invoiceNumber) {
                    $message->to($owner->email)
                        ->subject("Invoice {$invoiceNumber} payment request");
                }
            );
        }

        Log::info('Manual M-Pesa invoice payment request queued and notified', [
            'invoice_id' => $invoice->id,
            'owner_id' => $owner->id,
            'site_id' => $invoice->site_id,
            'stk_initiated' => $stkInitiated,
        ]);
    }

    private function normalizePhone(?string $phone): ?string
    {
        if (!$phone) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone);

        if (str_starts_with($digits, '0') && strlen($digits) === 10) {
            $digits = '254' . substr($digits, 1);
        }

        if (str_starts_with($digits, '254') && strlen($digits) === 12) {
            return $digits;
        }

        return null;
    }
}

