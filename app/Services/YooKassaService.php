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
        $this->username = env('YOOKASSA_USERNAME'); // Ваш shopId
        $this->password = env('YOOKASSA_PASSWORD'); // Ваш secret key
    }

    public function create($amount)
    {
        $idempotenceKey = Uuid::uuid4()->toString();
        
        $response = Http::withBasicAuth($this->username, $this->password) // Добавьте эту строку
            ->withHeaders([
                'Idempotence-Key' => $idempotenceKey,
                'Content-Type' => 'application/json'
            ])
            ->post($this->baseUrl . '/payments', [
                "amount" => [
                    "value" => (string)$amount, // Используйте переданную сумму
                    "currency" => "RUB"
                ],
                "confirmation" => [
                    "type" => "embedded" // Уберите пробел в "embedded"
                ],
                "capture" => true,
            ]);
        
        \Log::alert('YooKassa Response: ', $response->json());
        
        if ($response->failed()) {
            \Log::error('YooKassa Error: ', [
                'status' => $response->status(),
                'body' => $response->json()
            ]);
        }
        
        return $response->json();
    }
}