<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class DadataController extends Controller
{
    public function suggest(Request $request)
    {
        // 1. Получаем сырое содержимое JSON
        $data = $request->json()->all();

        // 2. Извлекаем query (теперь это точно строка)
        $query = $data['query'] ?? $request->input('query');

        // 3. Защита от пустого объекта
        if (!is_string($query) || strlen($query) < 2) {
            return response()->json(['suggestions' => []]);
        }

        $type = $data['type'] ?? $request->input('type', 'address');

        $apiKey = env('DADATA_API_KEY');
        $secretKey = env('DADATA_SECRET_KEY');

        if (!$apiKey || !$secretKey) {
            \Log::error('DaData: ключи не настроены в .env');
            return response()->json(['suggestions' => []], 500);
        }

        // 5. Запрос к DaData
        $response = Http::withHeaders([
            'Authorization' => 'Token ' . $apiKey,
            'X-Secret' => $secretKey,
            'Content-Type' => 'application/json',
        ])->post("https://suggestions.dadata.ru/suggestions/api/4_1/rs/suggest/{$type}", [
                    'query' => $query,
                    'count' => 10,
                ]);

        if ($response->failed()) {
            \Log::error('DaData API error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
        }

        return response()->json($response->json());
    }
}