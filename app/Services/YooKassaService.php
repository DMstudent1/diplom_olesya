<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Ramsey\Uuid\Uuid;

class YooKassaService
{
    private $username;
    private $password;
    private $baseUrl;

    public function __construct()
    {
        $this->baseUrl = env('YOOKASSA_BASE_URL', 'https://api.yookassa.ru/v3');
        $this->username = env('YOOKASSA_USERNAME');
        $this->password = env('YOOKASSA_PASSWORD');
    }

    public function create($uuid, $amount)
    {
        $response = Http::withBasicAuth($this->username, $this->password)
            ->withHeaders([
                'Idempotence-Key' => $uuid,
                'Content-Type' => 'application/json'
            ])
            ->post($this->baseUrl . '/payments', [
                "amount" => [
                    "value" => (string)$amount,
                    "currency" => "RUB"
                ],
                "confirmation" => [
                    "type" => "embedded"
                ],
                "capture" => true,
            ]);
        
        if ($response->failed()) {
            \Log::error('YooKassa Error: ', [
                'status' => $response->status(),
                'body' => $response->json()
            ]);
        }
        \Log::info($response);
        return $response->json();
    }
    public function check($id)
    {
        $response = Http::withBasicAuth($this->username, $this->password)->get($this->baseUrl . "/payments/{$id}");
        return $response->json()['status'];
    }
}