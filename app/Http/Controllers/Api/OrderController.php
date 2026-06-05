<?php

namespace App\Http\Controllers\Api;

use App\Models\Cart;
use App\Services\CdekService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;

class OrderController extends Controller
{
    protected $cdek;

    public function __construct(CdekService $cdek)
    {
        $this->cdek = $cdek;
    }

    public function getDeliveryPoints()
    {
        $user = Auth::user();
        return $this->cdek->getDeliveryPointsFromCity($user->address);
    }

    public function calculator(Request $request)
    {
        $code = $request->input('code');
        $user = Auth::user();
        $city = $user->address;
        $cartCount = $user->cart_items_count;
        \Log::alert($cartCount);
        return $this->cdek->calculate($city, $code, $cartCount);
    }


    
    /**
     * Создание заказа СДЭК из корзины пользователя
     */
    public function createCdekOrderFromCart(Request $request)
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json(['error' => 'Пользователь не авторизован'], 401);
        }

        // Получаем корзину пользователя (через отношение)
        $cart = $user->cart;

        if (!$cart) {
            return response()->json(['error' => 'Корзина не найдена'], 400);
        }

        // Получаем товары из корзины с количеством
        $cartProducts = $cart->products()->get();

        if ($cartProducts->isEmpty()) {
            return response()->json(['error' => 'Корзина пуста'], 400);
        }

        // Формируем массив товаров для СДЭК
        $items = [];
        $totalWeight = 0;
        $totalPrice = 0;

        foreach ($cartProducts as $product) {
            $quantity = $product->pivot->quantity;
            $itemWeight = (500) * $quantity;
            
            $items[] = [
                'name' => $product->name,
                'ware_key' => (string) $product->id,
                'payment' => ['value' => (float) $product->price],
                'cost' => (float) $product->price,
                'weight' => (int) (500),
                'amount' => (int) $quantity
            ];
            
            $totalWeight += $itemWeight;
            $totalPrice += $product->price * $quantity;
        }

        // Данные получателя (из запроса или из профиля пользователя)
        $recipientName = $user->name;
        $phone = $user->phone;
        $email = $user->email;

        $deliveryPoint = $request->input('point');

        if (!$deliveryPoint) {
            return response()->json(['error' => 'Не выбран пункт выдачи'], 400);
        }

        if (!$phone) {
            return response()->json(['error' => 'Не указан номер телефона'], 400);
        }

        $orderData = [
            'type' => 1,
            'number' => 'ORDER_' . time() . '_' . $user->id,
            'tariff_code' => 480,
            'comment' => 'Заказ из питомника саженцев',
            'shipment_point' => env('CDEK_SHIPMENT_POINT', 'SRK6'),
            'delivery_point' => $deliveryPoint,
            'recipient' => [
                'name' => $recipientName,
                'phones' => [
                    ['number' => $phone]
                ],
                'email' => $email
            ],
            'packages' => $this->preparePackages($items, $totalWeight)
        ];

        // Отправляем запрос в СДЭК
        $result = $this->cdek->createOrder($orderData);

        // Логируем результат для отладки
        \Log::info('CDEK Create Order Result', $result);

        if (isset($result['entity']['uuid'])) {
            // Сохраняем заказ в БД
            $order = $this->saveOrder($result['entity']['uuid'], $user, $cart, $totalPrice, $deliveryPoint, $result);
            
            return response()->json([
                'success' => true,
                'message' => 'Заказ успешно создан',
                'order' => [
                    'uuid' => $result['entity']['uuid'],
                    'cdek_number' => $result['entity']['cdek_number'] ?? null,
                    'status' => 'ACCEPTED'
                ]
            ]);
        }

        // Обработка ошибок
        $errorMessage = 'Ошибка создания заказа';
        if (isset($result['requests'][0]['errors'][0]['message'])) {
            $errorMessage = $result['requests'][0]['errors'][0]['message'];
        } elseif (isset($result['errors'][0]['message'])) {
            $errorMessage = $result['errors'][0]['message'];
        }

        return response()->json([
            'success' => false,
            'error' => $errorMessage,
            'details' => $result
        ], 500);
    }

    /**
     * Подготовка упаковок
     */
    private function preparePackages($items, $totalWeight)
    {
        $maxItemsPerPackage = 10;
        $maxWeightPerPackage = 15000;
        
        $packages = [];
        $packageNumber = 1;
        $currentPackage = [
            'number' => (string) $packageNumber,
            'weight' => 0,
            'items' => []
        ];
        
        foreach ($items as $item) {
            $itemWeight = $item['weight'] * $item['amount'];
            
            if (($currentPackage['weight'] + $itemWeight > $maxWeightPerPackage) ||
                count($currentPackage['items']) >= $maxItemsPerPackage) {
                
                if (!empty($currentPackage['items'])) {
                    $packages[] = $currentPackage;
                    $packageNumber++;
                    $currentPackage = [
                        'number' => (string) $packageNumber,
                        'weight' => 0,
                        'items' => []
                    ];
                }
            }
            
            $currentPackage['items'][] = $item;
            $currentPackage['weight'] += $itemWeight;
        }
        
        if (!empty($currentPackage['items'])) {
            $packages[] = $currentPackage;
        }
        
        foreach ($packages as &$package) {
            $package['length'] = 30;
            $package['width'] = 20;
            $package['height'] = 15;
            $package['comment'] = 'Саженцы, бережно';
        }
        
        return $packages;
    }

    /**
     * Сохранение заказа в БД
     */
    private function saveOrder($cdekUuid, $user, $cart, $totalPrice, $deliveryPoint, $cdekResponse)
    {
        // Создаем заказ
        $order = \App\Models\Order::create([
            'user_id' => $user->id,
            'cdek_uuid' => $cdekUuid,
            'cdek_number' => $cdekResponse['entity']['cdek_number'] ?? null,
            'total_price' => $totalPrice,
            'delivery_point' => $deliveryPoint,
            'status' => 'pending',
            'cart_id' => $cart->id
        ]);
        
        // Переносим товары из корзины в заказ
        foreach ($cart->products as $product) {
            $order->products()->attach($product->id, [
                'quantity' => $product->pivot->quantity,
                'price' => $product->price
            ]);
        }
        
        // Очищаем корзину
        $cart->products()->detach();
        
        return $order;
    }

    /**
     * Проверка статуса заказа
     */
    public function checkOrderStatus($uuid)
    {
        $order = \App\Models\Order::where('cdek_uuid', $uuid)->first();
        
        if (!$order) {
            return response()->json(['error' => 'Заказ не найден'], 404);
        }
        
        $status = $this->cdek->getOrderStatus($uuid);
        
        if (isset($status['entity']['cdek_number'])) {
            // Обновляем статус в БД
            $cdekStatus = $status['entity']['statuses'][0]['name'] ?? null;
            $order->update([
                'cdek_number' => $status['entity']['cdek_number'],
                'status' => $cdekStatus
            ]);
            
            return response()->json([
                'success' => true,
                'cdek_number' => $status['entity']['cdek_number'],
                'status' => $cdekStatus,
                'delivery_sum' => $status['entity']['delivery_detail']['delivery_sum'] ?? null
            ]);
        }
        
        return response()->json($status);
    }
}