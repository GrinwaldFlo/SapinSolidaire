<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AddressValidationService
{
    protected ?string $apiKey;
    protected string $apiUrl = 'https://webservices.post.ch:17017/IN_SYNSYN_EXT/v1/';

    public function __construct()
    {
        $this->apiKey = config('services.swisspost.api_key');
    }

    /**
     * Validate a Swiss address.
     *
     * @return array{valid: bool, message: ?string, suggestions: array}
     */
    public function validate(string $street, string $postalCode, string $city): array
    {
        // If no API key configured, accept address without validation
        if (empty($this->apiKey)) {
            return [
                'valid' => true,
                'message' => null,
                'suggestions' => [],
            ];
        }

        try {
            $response = Http::withBasicAuth($this->apiKey, '')
                ->timeout(10)
                ->post($this->apiUrl.'addresses/validate', [
                    'street' => $street,
                    'zip' => $postalCode,
                    'city' => $city,
                    'country' => 'CH',
                ]);

            if ($response->failed()) {
                // API error, accept address
                Log::warning('Swiss Post API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [
                    'valid' => true,
                    'message' => null,
                    'suggestions' => [],
                ];
            }

            $data = $response->json();

            if (isset($data['valid']) && $data['valid']) {
                return [
                    'valid' => true,
                    'message' => null,
                    'suggestions' => [],
                ];
            }

            return [
                'valid' => false,
                'message' => 'L\'adresse saisie n\'est pas reconnue. Veuillez vérifier et corriger.',
                'suggestions' => $data['suggestions'] ?? [],
            ];
        } catch (\Exception $e) {
            // On error, accept the address
            Log::warning('Swiss Post API exception', [
                'message' => $e->getMessage(),
            ]);

            return [
                'valid' => true,
                'message' => null,
                'suggestions' => [],
            ];
        }
    }
}
