<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AddressValidationService
{
    protected ?string $apiUser;
    protected ?string $apiPassword;
    protected string $apiUrl = 'https://webservices.post.ch:17023/IN_SYNSYN_EXT/REST/v1/';

    public function __construct()
    {
        $this->apiUser = config('services.swisspost.api_user');
        $this->apiPassword = config('services.swisspost.api_psw');
    }

    /**
     * Validate a Swiss address.
     *
     * @return array{Valide: bool, Message: string, FormatedAddress: array}
     */
    public function validate(string $streetname, string $houseNo, string $zipcode, string $townname): array
    {
        $streetname = trim($streetname);
        $houseNo    = trim($houseNo);
        $zipcode    = trim($zipcode);
        $townname   = trim($townname);

        if (empty($streetname) || empty($houseNo) || empty($zipcode) || empty($townname))
            return ['Valide' => false, 'Message' => "Merci de remplir tous les champs de l'adresse", 'FormatedAddress' => []];

        $verification = $this->buildingverification4($streetname, $houseNo, $zipcode, $townname);
        Log::debug('Swiss Post buildingverification4 result', ['verification' => $verification]);
        if (empty($verification))
            return ['Valide' => true, 'Message' => '', 'FormatedAddress' => []];

        if ($verification["PSTAT"] == 1 || $verification["PSTAT"] == 2)
            return ['Valide' => true, 'Message' => '', 'FormatedAddress' => $verification];

        if ($verification["PSTAT"] >= 6 || $verification["PSTAT"] == 4)
            return ['Valide' => false, 'Message' => 'Adresse invalide, merci de la contrôler', 'FormatedAddress' => $verification];

        if ($zipcode != $verification["ZipCode"])
            return ['Valide' => false, 'Message' => 'Code postal éroné', 'FormatedAddress' => $verification];

        if (!str_contains($verification["TownName"], $townname))
            return ['Valide' => false, 'Message' => 'Ville éronée', 'FormatedAddress' => $verification];

        return ['Valide' => true, 'Message' => '', 'FormatedAddress' => $verification];
    }

    /**
     * Validate a Swiss address.
     *
     * @return array{Valide: bool, Message: string, FormatedAddress: array}
     */
    public function validateTown(string $townname): array
    {
        $townname = trim($townname);


        if (empty($townname))
            return ['Valide' => false, 'Message' => "Merci de remplir la ville", 'FormatedAddress' => []];

        $verification = $this->buildingverification4('', '', '', $townname);
        Log::debug('Swiss Post buildingverification4 result', ['verification' => $verification]);
        if (empty($verification))
            return ['Valide' => false, 'Message' => 'Erreur de réception', 'FormatedAddress' => []];


        //TODO Fix needed
        return ['Valide' => true, 'Message' => "", 'FormatedAddress' => $verification];



        if ($verification["PSTAT"] == 1 || $verification["PSTAT"] == 2)
            return ['Valide' => true, 'Message' => '', 'FormatedAddress' => $verification];

        if ($verification["PSTAT"] == 6)
            return ['Valide' => true, 'Message' => "", 'FormatedAddress' => $verification];

        if ($verification["PSTAT"] >= 6 || $verification["PSTAT"] == 4)
            return ['Valide' => false, 'Message' => "Ville $townname invalide", 'FormatedAddress' => $verification];

        return ['Valide' => true, 'Message' => '', 'FormatedAddress' => $verification];
    }

    /**
     * Raw Swiss Post building verification call.
     */
    public function buildingverification4(string $streetname, string $houseNo, string $zipcode, string $townname): array
    {
        try {
            $response = Http::withBasicAuth($this->apiUser, $this->apiPassword)
                ->withoutVerifying()
                ->timeout(10)
                ->get($this->apiUrl.'buildingverification4', [
                    'streetname' => $streetname,
                    'houseNo' => $houseNo,
                    'zipcode' => $zipcode,
                    'townname' => $townname,
                ]);

            if ($response->failed()) {
                Log::warning('Swiss Post buildingverification4 API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'query' => [
                        'streetname' => $streetname,
                        'houseNo' => $houseNo,
                        'zipcode' => $zipcode,
                        'townname' => $townname,
                    ],
                ]);
            }

            $data = $response->json() ?? [];

            return $data['QueryBuildingVerification4Result']['BuildingVerificationData'] ?? [];
        } catch (\Exception $e) {
            Log::warning('Swiss Post buildingverification4 API exception', [
                'message' => $e->getMessage(),
                'query' => [
                    'streetname' => $streetname,
                    'houseNo' => $houseNo,
                    'zipcode' => $zipcode,
                    'townname' => $townname,
                ],
            ]);

            return [];
        }
    }
}
