<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CdekService
{
    private $baseUrl;
    private $shipmentLocation;
    private $shipmentPoint;
    private $shipmentCode;
    private $clientId;
    private $clientSecret;
    private $tokenCacheKey = 'cdek_access_token';

    public function __construct()
    {
        $this->baseUrl = env('CDEK_BASE_URL', 'https://api.edu.cdek.ru');
        $this->shipmentPoint = env('CDEK_SHIPMENT_POINT');
        $this->shipmentCode = env('CDEK_SHIPMENT_CODE');
        $this->shipmentLocation = env('CDEK_SHIPMENT_LOCATION');
        $this->clientId = env('CDEK_CLIENT_ID');
        $this->clientSecret = env('CDEK_CLIENT_SECRET');
    }

    public function getAccessToken($forceRefresh = false)
    {
        Log::info('getAccessToken called', [
            'forceRefresh' => $forceRefresh,
            'cache_has' => Cache::has($this->tokenCacheKey)
        ]);

        if ($forceRefresh) {
            Cache::forget($this->tokenCacheKey);
            Log::info('Cache cleared due to forceRefresh');
        }

        $token = Cache::remember($this->tokenCacheKey, 3300, function () {
            Log::info('Attempting to get new token from CDEK API');

            $response = Http::post($this->baseUrl . '/v2/oauth/token', [
                'grant_type' => 'client_credentials',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret
            ]);

            Log::info('CDEK token response status', ['status' => $response->status()]);

            $data = $response->json();

            Log::info('CDEK token response data', [
                'has_access_token' => isset($data['access_token']),
                'response_keys' => array_keys($data),
                'error' => $data['error'] ?? null,
                'error_description' => $data['error_description'] ?? null
            ]);

            if (!isset($data['access_token'])) {
                Log::error('CDEK Auth failed - full response', [
                    'body' => $response->body(),
                    'status' => $response->status()
                ]);
                throw new \Exception('Не удалось получить токен СДЭК: ' . ($data['error_description'] ?? 'Unknown error'));
            }

            Log::info('Successfully got new token', [
                'token_preview' => substr($data['access_token'], 0, 20) . '...'
            ]);

            return $data['access_token'];
        });

        Log::info('getAccessToken returning', [
            'token_preview' => substr($token, 0, 20) . '...'
        ]);

        return $token;
    }

    private function makeRequest($method, $url, $params = [], $isJson = false)
    {
        $maxRetries = 2;
        $lastResponse = null;

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $forceRefresh = ($attempt > 1);
                $token = $this->getAccessToken($forceRefresh);

                Log::info("Making CDEK request (attempt $attempt)", [
                    'method' => $method,
                    'url' => $url,
                    'force_token_refresh' => $forceRefresh,
                    'token_preview' => substr($token, 0, 20) . '...'
                ]);

                $http = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $token
                ]);

                if ($isJson) {
                    $http = $http->withHeaders(['Content-Type' => 'application/json']);
                }

                $response = $method === 'post'
                    ? $http->post($url, $params)
                    : $http->get($url, $params);

                Log::info("CDEK response status", [
                    'attempt' => $attempt,
                    'status' => $response->status()
                ]);

                if ($response->status() === 401 && $attempt < $maxRetries) {
                    Log::warning("CDEK 401 error, details:", [
                        'response_body' => $response->body(),
                        'attempt' => $attempt
                    ]);
                    Cache::forget($this->tokenCacheKey);
                    continue;
                }

                return $response;

            } catch (\Exception $e) {
                Log::error("Exception in makeRequest", [
                    'attempt' => $attempt,
                    'error' => $e->getMessage()
                ]);
                if ($attempt >= $maxRetries) {
                    throw $e;
                }
            }
        }

        Log::error("CDEK request failed after $maxRetries attempts");
        return $lastResponse;
    }

    public function getCities($cityName)
    {
        $response = $this->makeRequest(
            'get',
            $this->baseUrl . '/v2/location/suggest/cities',
            [
                'name' => $cityName,
                'country_code' => 'RU'
            ]
        );

        return $response ? $response->json() : [];
    }

    public function getCityCodeAndName($cityName)
    {
        $cities = $this->getCities($cityName);

        if (empty($cities)) {
            return null;
        }

        foreach ($cities as $city) {
            if (strpos($city['full_name'], $cityName . ',') === 0) {
                return [
                    'code' => $city['code'],
                    'name' => $city['full_name']
                ];
            }
        }

        return [
            'code' => $cities[0]['code'],
            'name' => $cities[0]['full_name']
        ];
    }

    public function getDeliveryPoints($code)
    {
        if ($code == '2766') {
            return [
                [
                    "code" => "KOV6",
                    "name" => "KOV6, Ковылкино, ул. Пролетарская, 13, 1А",
                    "uuid" => "d2bda230-7a90-4ba9-adc1-8eb7c6a776fe",
                    "nearest_station" => "Проф Тех Училище",
                    "work_time" => "Пн-Пт 10:00-19:00, Сб 10:00-16:00",
                    "phones" => [
                        ["number" => "+79603377376"]
                    ],
                    "email" => "e.poznyak@cdek.ru",
                    "type" => "PVZ",
                    "owner_code" => "CDEK",
                    "take_only" => false,
                    "is_handout" => true,
                    "is_reception" => true,
                    "is_dressing_room" => true,
                    "is_ltl" => false,
                    "have_cashless" => true,
                    "have_cash" => true,
                    "have_fast_payment_system" => false,
                    "allowed_cod" => true,
                    "office_image_list" => [
                        ["url" => "https://gateway.edu.cdek.ru/file-storage/web/object/office-photo/c6b63c8c-e79f-4f12-8481-6bee3c198265"],
                        ["url" => "https://gateway.edu.cdek.ru/file-storage/web/object/office-photo/86810067-0790-4abe-ab24-a5af84eaae6a"],
                        ["url" => "https://gateway.edu.cdek.ru/file-storage/web/object/office-photo/87e359cc-9034-4969-81d7-c061dee130db"]
                    ],
                    "work_time_list" => [
                        ["day" => 1, "time" => "10:00/19:00"],
                        ["day" => 2, "time" => "10:00/19:00"],
                        ["day" => 3, "time" => "10:00/19:00"],
                        ["day" => 4, "time" => "10:00/19:00"],
                        ["day" => 5, "time" => "10:00/19:00"],
                        ["day" => 6, "time" => "10:00/16:00"]
                    ],
                    "work_time_exception_list" => [],
                    "location" => [
                        "country_code" => "RU",
                        "region_code" => 68,
                        "region" => "Мордовия",
                        "city_code" => 2766,
                        "city" => "Ковылкино",
                        "fias_guid" => "1ccfdc3c-be0f-4e42-ab4d-98f90de972d9",
                        "postal_code" => "431350",
                        "longitude" => 54.038947,
                        "latitude" => 43.910945,
                        "address" => "ул. Пролетарская, 13, 1А",
                        "address_full" => "430024, Россия, Мордовия, Ковылкино, ул. Пролетарская, 13, 1А",
                        "city_uuid" => "ae42b8a7-6711-4f41-9b25-effc8c4d31c8"
                    ],
                    "ltl_acceptance_partners" => false,
                    "ltl_issuance_partners" => false,
                    "fulfillment" => false
                ]
            ];
        }

        $response = $this->makeRequest(
            'get',
            $this->baseUrl . '/v2/deliverypoints',
            [
                'city_code' => $code,
                'size' => 10
            ]
        );

        $data = $response ? $response->json() : [];

        if (empty($data) || !is_array($data)) {
            return ['error' => 'В городе нет пунктов выдачи'];
        }

        return $data;
    }


    public function calculate($city_code, $pvz_code, $city, $count)
    {
        $response = $this->makeRequest(
            'post',
            $this->baseUrl . '/v2/calculator/tariff',
            [
                'date' => now()->addDay()->format('Y-m-d\TH:i:sO'),
                'type' => 1,
                'tariff_code' => 480,
                'currency' => 1,
                'shipment_point' => $this->shipmentPoint,
                'delivery_point' => $pvz_code,
                "services" => null,
                'from_location' => ['code' => $this->shipmentCode, 'city' => $this->shipmentLocation],
                'to_location' => ['code' => $city_code, 'city' => $city],
                'packages' => [
                    "weight" => 500 * $count,
                    "length" => 30,
                    "width" => 20,
                    "height" => 15
                ]
            ],
            true
        );

        return $response ? $response->json() : ['error' => 'Ошибка расчета'];
    }

    public function createOrder(array $orderData)
    {
        $response = $this->makeRequest(
            'post',
            $this->baseUrl . '/v2/orders',
            $orderData,
            true
        );

        $data = $response ? $response->json() : [];
        Log::info('CDEK Create Order Response', $data);

        return $data;
    }

    public function check($uuid)
    {
        $response = $this->makeRequest(
            'get',
            $this->baseUrl . '/v2/orders/' . $uuid,
            []
        );
        \Log::info($response);

        return $response ? $response->json() : [];
    }
}