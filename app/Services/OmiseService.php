<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OmiseService
{
    protected string $publicKey;

    protected string $secretKey;

    protected string $apiVersion = '2019-05-29';

    protected string $baseUrl = 'https://api.omise.co';

    public function __construct()
    {
        $this->publicKey = (string) config('services.omise.public_key', env('OMISE_PUBLIC_KEY', ''));
        $this->secretKey = (string) config('services.omise.secret_key', env('OMISE_SECRET_KEY', ''));
    }

    public function isConfigured(): bool
    {
        return ! empty($this->secretKey) && ! empty($this->publicKey);
    }

    /**
     * Create PromptPay Charge (creates a source then a charge)
     *
     * @param  int  $amountInSatang  Amount in THB satang (1 THB = 100 satangs)
     * @param  array  $metadata  Custom metadata (user_id, plan_id, billing_interval)
     */
    public function createPromptPayCharge(int $amountInSatang, array $metadata = []): array
    {
        if (! $this->isConfigured()) {
            // Mock response for development / testing when API keys are not supplied
            $mockChargeId = 'chrg_test_'.bin2hex(random_bytes(8));

            return [
                'id' => $mockChargeId,
                'status' => 'pending',
                'amount' => $amountInSatang,
                'currency' => 'THB',
                'source' => [
                    'type' => 'promptpay',
                    'scannable_code' => [
                        'image' => [
                            'download_uri' => 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=00020101021229370016A000000677010111'.$mockChargeId,
                        ],
                    ],
                ],
                'is_mock' => true,
            ];
        }

        try {
            // 1. Create Source for PromptPay
            $sourceResponse = Http::withBasicAuth($this->publicKey, '')
                ->withHeaders(['Omise-Version' => $this->apiVersion])
                ->post("{$this->baseUrl}/sources", [
                    'type' => 'promptpay',
                    'amount' => $amountInSatang,
                    'currency' => 'thb',
                ]);

            if (! $sourceResponse->successful()) {
                Log::error('Omise PromptPay Source creation failed', ['response' => $sourceResponse->json()]);
                throw new Exception($sourceResponse->json('message') ?? 'Failed to create PromptPay source');
            }

            $sourceId = $sourceResponse->json('id');

            // 2. Create Charge with Source
            $chargeResponse = Http::withBasicAuth($this->secretKey, '')
                ->withHeaders(['Omise-Version' => $this->apiVersion])
                ->post("{$this->baseUrl}/charges", [
                    'amount' => $amountInSatang,
                    'currency' => 'thb',
                    'source' => $sourceId,
                    'metadata' => $metadata,
                    'return_uri' => route('billing.index'),
                ]);

            if (! $chargeResponse->successful()) {
                Log::error('Omise PromptPay Charge creation failed', ['response' => $chargeResponse->json()]);
                throw new Exception($chargeResponse->json('message') ?? 'Failed to create PromptPay charge');
            }

            return $chargeResponse->json();
        } catch (Exception $e) {
            Log::error('Omise Exception: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Create Credit Card Charge
     *
     * @param  string  $token  Card token from Omise.js
     */
    public function createCardCharge(int $amountInSatang, string $token, array $metadata = []): array
    {
        if (! $this->isConfigured()) {
            return [
                'id' => 'chrg_test_'.bin2hex(random_bytes(8)),
                'status' => 'successful',
                'paid' => true,
                'amount' => $amountInSatang,
                'currency' => 'THB',
                'is_mock' => true,
            ];
        }

        try {
            $chargeResponse = Http::withBasicAuth($this->secretKey, '')
                ->withHeaders(['Omise-Version' => $this->apiVersion])
                ->post("{$this->baseUrl}/charges", [
                    'amount' => $amountInSatang,
                    'currency' => 'thb',
                    'card' => $token,
                    'metadata' => $metadata,
                    'return_uri' => route('billing.index'),
                ]);

            if (! $chargeResponse->successful()) {
                Log::error('Omise Card Charge creation failed', ['response' => $chargeResponse->json()]);
                throw new Exception($chargeResponse->json('message') ?? 'Card charge failed');
            }

            return $chargeResponse->json();
        } catch (Exception $e) {
            Log::error('Omise Exception: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Retrieve Charge Status
     */
    public function getCharge(string $chargeId): array
    {
        if (str_starts_with($chargeId, 'chrg_test_')) {
            return [
                'id' => $chargeId,
                'status' => 'successful',
                'paid' => true,
                'is_mock' => true,
            ];
        }

        $response = Http::withBasicAuth($this->secretKey, '')
            ->withHeaders(['Omise-Version' => $this->apiVersion])
            ->get("{$this->baseUrl}/charges/{$chargeId}");

        if (! $response->successful()) {
            throw new Exception($response->json('message') ?? 'Failed to retrieve charge');
        }

        return $response->json();
    }
}
