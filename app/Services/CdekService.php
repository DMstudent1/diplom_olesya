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

    public function getDeliveryPoints($cityCode)
    {
        $response = $this->makeRequest(
            'get',
            $this->baseUrl . '/v2/deliverypoints',
            [
                'city_code' => $cityCode,
                'size' => 3
            ]
        );

        $data = $response ? $response->json() : [];

        if (empty($data) || !is_array($data)) {
            return ['error' => 'В городе нет пунктов выдачи'];
        }

        return $data;
    }

    public function getDeliveryPointsFromCity($city)
    {
        $city = $this->getCityCodeAndName($city);

        if (!$city || !isset($city['code'])) {
            return response()->json(['error' => 'Город не найден'], 404);
        }

        $data = $this->getDeliveryPoints($city['code']);
        return response()->json($data);
    }

    public function calculate($city, $code, $count)
    {
        $cityData = $this->getCityCodeAndName($city);

        if (!$cityData || !isset($cityData['code'])) {
            return ['error' => 'Город не найден'];
        }
        \Log::alert($count);
        $response = $this->makeRequest(
            'post',
            $this->baseUrl . '/v2/calculator/tariff',
            [
                'date' => now()->addDay()->format('Y-m-d\TH:i:sO'),
                'type' => 1,
                'tariff_code' => 480,
                'currency' => 1,
                'shipment_point' => $this->shipmentPoint,
                'delivery_point' => $code,
                "services" => null,
                'from_location' => ['code' => $this->shipmentCode, 'city' => $this->shipmentLocation],
                'to_location' => ['code' => $cityData['code'], 'city' => $cityData['name']],
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