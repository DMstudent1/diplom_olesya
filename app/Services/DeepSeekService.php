<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DeepSeekService
{
    protected $apiKey;
    protected $apiUrl = 'https://api.vsegpt.ru/v1/chat/completions';

    // Контекст магазина
    protected $shopContext = [];

    public function __construct()
    {
        $this->apiKey = env('VSEGPT_API_KEY');
        $this->loadShopContext();
    }

    public function generateDescription($productName)
    {
        $apiKey = env('VSEGPT_API_KEY');

        if (!$apiKey) {
            return 'API ключ не настроен. Добавьте VSEGPT_API_KEY в .env файл';
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
            'X-Title' => 'Laravel App',
        ])->post('https://api.vsegpt.ru/v1/chat/completions', [
            'model' => 'openai/gpt-4o-mini',
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
            'temperature' => 0.1,
        ]);

        $fullResponse = $response->json();

        Log::info('VseGPT API Response', $fullResponse);

        if (isset($fullResponse['error'])) {
            return 'Ошибка API: ' . ($fullResponse['error']['message'] ?? json_encode($fullResponse['error']));
        }

        if (!isset($fullResponse['choices']) || !is_array($fullResponse['choices'])) {
            return 'Неожиданный ответ от API: ' . json_encode($fullResponse);
        }

        return $fullResponse['choices'][0]['message']['content'];
    }

    /**
     * Загрузка контекста магазина (каталог, информация и т.д.)
     */
    protected function loadShopContext()
    {
        $this->shopContext = [
            'shop_name' => env('APP_NAME', 'Мой магазин'),
            'description' => 'Интернет-магазин для питомника растений',
            'categories' => $this->getCategories(),
            'policies' => [
                'delivery' => 'Доставка по всей России от 3 дней',
                'payment' => 'онлайн оплата',
                'returns' => 'Возврат в течение 14 дней'
            ]
        ];
    }

    /**
     * Основной метод для отправки сообщения и получения ответа консультанта
     */
    public function sendMessage($userMessage, $chatHistory = [], $sessionId = null)
    {
        if (!$this->apiKey) {
            return $this->errorResponse('API ключ не настроен. Добавьте VSEGPT_API_KEY в .env файл');
        }

        try {
            $systemPrompt = $this->buildSystemPrompt();

            $messages = [
                ['role' => 'system', 'content' => $systemPrompt],
                ...$this->formatChatHistory($chatHistory),
                ['role' => 'user', 'content' => $userMessage]
            ];

            $response = Http::timeout(30)
                ->retry(3, 100)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                    'X-Title' => 'Laravel Chat Assistant',
                ])
                ->post($this->apiUrl, [
                    'model' => 'openai/gpt-4o-mini',
                    'messages' => $messages,
                    'temperature' => 0.7,
                    'max_tokens' => 500,
                    'stream' => false
                ]);

            if (!$response->successful()) {
                Log::error('VseGPT API Error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                return $this->errorResponse('Сервис временно недоступен. Попробуйте позже.');
            }

            $fullResponse = $response->json();

            if (!isset($fullResponse['choices'][0]['message']['content'])) {
                Log::warning('Unexpected API response structure', $fullResponse);
                return $this->errorResponse('Получен некорректный ответ от сервера');
            }

            $assistantReply = $fullResponse['choices'][0]['message']['content'];
            $products = $this->findRelevantProducts($userMessage);

            if ($sessionId) {
                $this->saveChatHistory($sessionId, $userMessage, $assistantReply);
            }

            return [
                'success' => true,
                'reply' => $assistantReply,
                'products' => $products,
                'suggestions' => $this->getSuggestions($userMessage)
            ];

        } catch (\Exception $e) {
            Log::error('Chat service exception: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Произошла ошибка. Пожалуйста, попробуйте позже.');
        }
    }

    /**
     * Формирование системного промпта с контекстом магазина
     */
    protected function buildSystemPrompt()
    {
        $categories = implode(', ', array_column($this->shopContext['categories'], 'name'));

        return "
Ты — ИИ-консультант интернет-магазина \"{$this->shopContext['shop_name']}\".

О магазине:
{$this->shopContext['description']}

Категории товаров:
{$categories}

Условия работы:
- Доставка: {$this->shopContext['policies']['delivery']}
- Оплата: {$this->shopContext['policies']['payment']}
- Возврат: {$this->shopContext['policies']['returns']}

Правила общения:
1. Отвечай дружелюбно, вежливо и профессионально
2. Используй эмодзи для эмоциональной окраски (но не переусердствуй)
3. Если спрашивают о товаре - предложи конкретные позиции из каталога
4. Помогай с выбором, сравнивай характеристики
5. Если не знаешь ответа - предложи связаться с поддержкой
6. Не выдумывай информацию о товарах, которых нет в каталоге
7. Будь полезным и решай проблемы пользователя
8. Отвечай на русском языке

Теперь ответь на вопрос пользователя, используя контекст магазина.
        ";
    }

    /**
     * Форматирование истории чата для API
     */
    protected function formatChatHistory($history)
    {
        if (empty($history) || !is_array($history)) {
            return [];
        }

        $formatted = [];
        $recentHistory = array_slice($history, -10);

        foreach ($recentHistory as $message) {
            $role = $message['type'] === 'user' ? 'user' : 'assistant';
            $formatted[] = [
                'role' => $role,
                'content' => $message['text']
            ];
        }

        return $formatted;
    }

    /**
     * Поиск релевантных товаров по сообщению пользователя
     */
    protected function findRelevantProducts($message)
    {
        $keywords = $this->extractKeywords($message);
        if (empty($keywords)) {
            return [];
        }

        try {
            $products = \DB::table('products')
                ->where('count', '>', 0)
                ->where(function ($query) use ($keywords) {
                    foreach ($keywords as $keyword) {
                        $query->orWhere('name', 'like', "%{$keyword}%")
                            ->orWhere('description', 'like', "%{$keyword}%");
                    }
                })
                ->limit(5)
                ->get(['id', 'name', 'price', 'image_url'])
                ->toArray();

            return $products;
        } catch (\Exception $e) {
            Log::error('Product search error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Извлечение ключевых слов из сообщения
     */
    protected function extractKeywords($message)
    {
        $stopWords = ['какой', 'где', 'когда', 'сколько', 'почему', 'этот', 'эта', 'это', 'как', 'что', 'кто'];

        $words = explode(' ', mb_strtolower($message));
        $keywords = array_filter($words, function ($word) use ($stopWords) {
            return mb_strlen($word) > 3 && !in_array($word, $stopWords);
        });

        return array_unique($keywords);
    }

    /**
     * Генерация подсказок для пользователя
     */
    protected function getSuggestions($message)
    {
        $suggestions = [];

        if (stripos($message, 'привет') !== false) {
            $suggestions = [
                'Какие товары у вас есть?',
                'Расскажите о доставке',
                'Как оплатить заказ?'
            ];
        } elseif (stripos($message, 'товар') !== false || stripos($message, 'купить') !== false) {
            $suggestions = [
                'Что сейчас в наличии?',
                'Какие есть скидки?',
                'Есть ли доставка в мой город?'
            ];
        }

        return $suggestions;
    }

    /**
     * Сохранение истории чата
     */
    protected function saveChatHistory($sessionId, $userMessage, $assistantReply)
    {
        \Cache::put("chat_session_{$sessionId}_last", [
            'user' => $userMessage,
            'assistant' => $assistantReply,
            'timestamp' => now()
        ], now()->addHours(24));
    }

    /**
     * Получение категорий товаров
     */
    protected function getCategories()
    {
        try {
            return \DB::table('categories')
                ->limit(10)
                ->get(['id', 'name'])
                ->toArray();
        } catch (\Exception $e) {
            Log::error('Categories fetch error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Формирование ответа с ошибкой
     */
    protected function errorResponse($message)
    {
        return [
            'success' => false,
            'reply' => $message,
            'products' => [],
            'suggestions' => []
        ];
    }

    /**
     * Метод для потоковой передачи ответа (Server-Sent Events)
     */
    public function streamMessage($userMessage, $chatHistory = [])
    {
        if (!$this->apiKey) {
            return;
        }

        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');

        $systemPrompt = $this->buildSystemPrompt();
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ...$this->formatChatHistory($chatHistory),
            ['role' => 'user', 'content' => $userMessage]
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post($this->apiUrl, [
            'model' => 'openai/gpt-4o-mini',
            'messages' => $messages,
            'temperature' => 0.7,
            'stream' => true
        ]);

        $body = $response->getBody();

        while (!$body->eof()) {
            $line = $body->read(1024);
            echo "data: " . json_encode(['chunk' => $line]) . "\n\n";
            ob_flush();
            flush();
        }
    }
}