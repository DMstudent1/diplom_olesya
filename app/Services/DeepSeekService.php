<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class DeepSeekService
{
    public function generateDescription($productName)
    {
        $apiKey = env('VSEGPT_API_KEY'); // Используем ключ от VseGPT
        
        if (!$apiKey) {
            return 'API ключ не настроен. Добавьте VSEGPT_API_KEY в .env файл';
        }
        
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
            'X-Title' => 'Laravel App', // опционально - передача информации об источнике
        ])->post('https://api.vsegpt.ru/v1/chat/completions', [  // ← ИСПРАВЛЕНО: правильный URL
            'model' => 'openai/gpt-4o-mini',  // ← модель из вашего Python примера
            'messages' => [
                [
                    'role' => 'system', 
                    'content' => 'Ты — профессиональный копирайтер для питомника растений. Пиши красивые, продающие описания на русском языке. Отвечай только текстом описания, без лишних слов.'
                ],
                [
                    'role' => 'user', 
                    'content' => "Напиши привлекательное описание для растения: {$productName}. Напиши 2-4 предложения, добавь один подходящий эмодзи."
                ]
            ],
            'temperature' => 0.1,  // из вашего Python примера
        ]);
        
        $fullResponse = $response->json();
        
        // Логируем полный ответ для отладки
        \Log::info('VseGPT API Response', $fullResponse);
        
        // Проверяем на ошибки
        if (isset($fullResponse['error'])) {
            return 'Ошибка API: ' . ($fullResponse['error']['message'] ?? json_encode($fullResponse['error']));
        }
        
        // Проверяем, есть ли choices
        if (!isset($fullResponse['choices']) || !is_array($fullResponse['choices'])) {
            return 'Неожиданный ответ от API: ' . json_encode($fullResponse);
        }
        
        return $fullResponse['choices'][0]['message']['content'];
    }
}