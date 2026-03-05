<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MpesaFeeService
{
    /**
     * Resolve the B2C transfer fee for a worker payout.
     * API-based lookup is attempted first, with local tier fallback.
     */
    public function resolveB2CFee(float $amount, ?string $phoneNumber = null): array
    {
        $normalizedAmount = max(0, round($amount, 2));

        if ($normalizedAmount <= 0) {
            return [
                'fee' => 0.0,
                'source' => 'none',
                'meta' => ['reason' => 'non_positive_amount'],
            ];
        }

        $apiResult = $this->resolveFromApi($normalizedAmount, $phoneNumber);
        if ($apiResult !== null) {
            return $apiResult;
        }

        $tierFee = $this->resolveFromTiers($normalizedAmount);

        return [
            'fee' => $tierFee,
            'source' => 'tiers',
            'meta' => [
                'amount' => $normalizedAmount,
            ],
        ];
    }

    /**
     * Optional provider API lookup for exact transfer fees.
     */
    private function resolveFromApi(float $amount, ?string $phoneNumber = null): ?array
    {
        if (!config('services.mpesa.fee_api.enabled', false)) {
            return null;
        }

        $url = config('services.mpesa.fee_api.url');
        if (!$url) {
            return null;
        }

        $token = config('services.mpesa.fee_api.token');
        $timeout = (int) config('services.mpesa.fee_api.timeout', 10);

        try {
            $request = Http::timeout($timeout)->acceptJson();

            if (!empty($token)) {
                $request = $request->withToken($token);
            }

            $response = $request->post($url, [
                'provider' => 'mpesa',
                'transaction_type' => 'b2c',
                'amount' => $amount,
                'currency' => config('services.mpesa.fee_api.currency', 'KES'),
                'phone_number' => $phoneNumber,
            ]);

            if (!$response->successful()) {
                Log::warning('MPesa fee API lookup failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $payload = $response->json();
            $resolvedFee = is_array($payload) ? ($payload['fee'] ?? $payload['data']['fee'] ?? null) : null;

            if (!is_numeric($resolvedFee)) {
                Log::warning('MPesa fee API returned invalid fee', [
                    'payload' => $payload,
                ]);

                return null;
            }

            return [
                'fee' => round((float) $resolvedFee, 2),
                'source' => 'api',
                'meta' => [
                    'amount' => $amount,
                ],
            ];
        } catch (\Throwable $e) {
            Log::warning('MPesa fee API lookup exception', [
                'error' => $e->getMessage(),
                'amount' => $amount,
            ]);

            return null;
        }
    }

    /**
     * Local fallback tier pricing for B2C transfer cost.
     */
    private function resolveFromTiers(float $amount): float
    {
        $tiers = config('services.mpesa.b2c_fee_tiers', []);

        foreach ($tiers as $tier) {
            $min = (float) ($tier['min'] ?? 0);
            $max = (float) ($tier['max'] ?? 0);
            $fee = $tier['fee'] ?? null;

            if (!is_numeric($fee)) {
                continue;
            }

            if ($amount >= $min && ($max <= 0 || $amount <= $max)) {
                return round((float) $fee, 2);
            }
        }

        return (float) config('services.mpesa.default_b2c_fee', 25);
    }
}
