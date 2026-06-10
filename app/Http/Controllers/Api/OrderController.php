<?php

namespace App\Http\Controllers\Api;

use App\Mail\SuccessPayment;
use App\Models\Cart;
use App\Models\Order;
use App\Models\User;
use App\Services\CdekService;
use App\Services\YooKassaService;
use Date;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Mail;
use Ramsey\Uuid\Uuid;
use Yajra\DataTables\DataTables;

class OrderController extends Controller
{
    protected $cdek;
    protected $yooKassa;

    public function __construct(CdekService $cdek, YooKassaService $yooKassa)
    {
        $this->cdek = $cdek;
        $this->yooKassa = $yooKassa;
    }

    public function getCities(Request $request)
    {
        $city = $request->input('city');
        return $this->cdek->getCities($city);
    }

    public function getUserOrdersPaginated(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Пользователь не авторизован'
            ], 401);
        }

        $perPage = $request->input('per_page', 10);
        $status = $request->input('status', null);

        // Строим запрос
        $query = $user->orders();

        // Фильтр по статусу
        if ($status && in_array($status, ['pending', 'succeeded', 'canceled', 'processing', 'delivered'])) {
            $query->where('status', $status);
        }

        // Пагинация
        $orders = $query->orderBy('created_at', 'desc')->paginate($perPage);

        // Обновляем статусы
        foreach ($orders as $order) {
            $this->updateOrderStatusIfNeeded($order);
        }

        // Формируем ответ
        $ordersData = [];
        foreach ($orders as $order) {
            $productIds = json_decode($order->products, true);
            $productsCount = $productIds ? count($productIds) : 0;

            // Получаем только первое изображение для превью
            $previewImage = null;
            if ($productIds) {
                $firstProduct = \App\Models\Product::where('id', $productIds[0])->with('media')->first();
                if ($firstProduct && $firstProduct->media && $firstProduct->media->first()) {
                    $previewImage = $firstProduct->media->first()->original_url;
                }
            }

            $ordersData[] = [
                'id' => $order->id,
                'uuid' => $order->uuid,
                'status' => $order->status,
                'sum' => (float) $order->sum,
                'products_count' => $productsCount,
                'preview_image' => $previewImage,
                'delivery_uuid' => $order->delivery_uuid,
                'created_at' => $order->created_at->format('Y-m-d H:i:s'),
                'formatted_date' => $order->created_at->format('d.m.Y'),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $ordersData,
            'current_page' => $orders->currentPage(),
            'last_page' => $orders->lastPage(),
            'per_page' => $orders->perPage(),
            'total' => $orders->total(),
        ]);
    }

    public function getUserOrders()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Пользователь не авторизован'
            ], 401);
        }

        // Получаем все заказы пользователя, сортируем по дате создания (новые сверху)
        $orders = $user->orders()
            ->orderBy('created_at', 'desc')
            ->get();

        // Обновляем статусы для всех заказов
        foreach ($orders as $order) {
            $this->updateOrderStatusIfNeeded($order);
        }

        // Формируем ответ
        $result = [];
        foreach ($orders as $order) {
            $productIds = json_decode($order->products, true);
            $products = [];

            if ($productIds) {
                $products = \App\Models\Product::whereIn('id', $productIds)
                    ->with('media')
                    ->get();
            }

            $result[] = [
                'id' => $order->id,
                'uuid' => $order->uuid,
                'products' => $products,
                'status' => $order->status,
                'sum' => (float) $order->sum,
                'delivery_point' => $order->delivery_point,
                'delivery_uuid' => $order->delivery_uuid,
                'created_at' => $order->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $order->updated_at->format('Y-m-d H:i:s'),
            ];
        }

        return response()->json([
            'success' => true,
            'orders' => $result,
            'total' => count($result)
        ]);
    }

    public function getUserOrder($id)
    {
        $user = Auth::user();

        $order = $user->orders()->where('id', $id)->first();

        if (!$order) {
            return response()->json([
                'success' => false
            ], 404);
        }

        $this->updateOrderStatusIfNeeded($order);

        $productIds = json_decode($order->products, true);
        $products = [];

        if ($productIds) {
            $products = \App\Models\Product::whereIn('id', $productIds)->with('media')->get();
        }

        return response()->json([
            'success' => true,
            'order' => [
                'id' => $order->id,
                'products' => $products,
                'status' => $order->status,
                'sum' => $order->sum,
                'created_at' => $order->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $order->updated_at->format('Y-m-d H:i:s'),
            ]
        ]);
    }
    protected function updateOrderStatusIfNeeded(Order $order)
    {
        // Проверяем, нужно ли обновлять статус (не обновлялся более 1 дня)
        if ($order->updated_at->diffInDays(now()) >= 1) {

            // Проверяем статус платежа в YooKassa для pending заказов
            if ($order->status == 'pending') {
                try {
                    $paymentStatus = $this->yooKassa->check($order->payment_id);

                    if ($paymentStatus == 'succeeded') {
                        $order->status = 'succeeded';
                        $order->save();
                        \Log::info("Order {$order->id} status updated to succeeded");
                    } elseif ($paymentStatus == 'canceled') {
                        $order->status = 'canceled';
                        $order->save();
                        \Log::info("Order {$order->id} status updated to canceled");
                    } else {
                        $order->updated_at = now();
                        $order->save();
                    }
                } catch (\Exception $e) {
                    \Log::error("Error checking payment for order {$order->id}: " . $e->getMessage());
                    $order->updated_at = now();
                    $order->save();
                }
            }

            // Проверяем статус доставки для отправленных заказов
            if ($order->delivery_uuid && in_array($order->status, ['succeeded', 'processing'])) {
                try {
                    $deliveryStatus = $this->cdek->check($order->delivery_uuid);

                    // Обновляем статус на основе ответа СДЭК
                    if ($deliveryStatus == 'delivered') {
                        $order->status = 'delivered';
                        $order->save();
                        \Log::info("Order {$order->id} status updated to delivered");
                    } elseif ($deliveryStatus == 'processing') {
                        $order->status = 'processing';
                        $order->save();
                        \Log::info("Order {$order->id} status updated to processing");
                    } else {
                        $order->updated_at = now();
                        $order->save();
                    }
                } catch (\Exception $e) {
                    \Log::error("Error checking delivery for order {$order->id}: " . $e->getMessage());
                    $order->updated_at = now();
                    $order->save();
                }
            }
        }
    }


    public function getDeliveryPoints(Request $request)
    {
        $code = $request->input('code');
        return $this->cdek->getDeliveryPoints($code);
    }

    public function calculator(Request $request)
    {
        $city_code = $request->input('city_code');
        $city = $request->input('city');
        $pvz_code = $request->input('pvz_code');
        $uuid = Uuid::uuid4()->toString();
        $user = Auth::user();
        $cartCount = $user->cart_items_count;
        $cdekInfo = $this->cdek->calculate($city_code, $pvz_code, $city, $cartCount);
        $paymentInfo = $this->createPayment($uuid, $user, $cdekInfo['delivery_sum']);
        $products = $user->cart->products;
        $order = Order::updateOrCreate(
            [
                'user_id' => $user->id,
                'status' => 'pending'
            ],
            [
                'uuid' => $uuid,
                'delivery_point' => $request->input('pvz_code'),
                'payment_id' => $paymentInfo['id'],
                'products' => $products->pluck('id')->toJson(),
                'sum' => $paymentInfo['amount']['value'],
                'updated_at' => Date::now(),
            ]
        );
        return response()->json([
            'delivery' => $cdekInfo,
            'payment' => $paymentInfo
        ]);
    }

    public function createPayment($uuid, User $user, $deliverySum)
    {
        $products = $user->cart->products;
        $totalProductPrice = $products->sum('price');
        $totalPrice = $totalProductPrice + $deliverySum;
        return $this->yooKassa->create($uuid, $totalPrice);
    }

    public function success(Request $request)
    {

        $user = Auth::user();
        $order = $user->orders()->where('status', 'pending')->first();
        $status = $this->yooKassa->check($order->payment_id);
        if ($status == 'succeeded') {
            $orderData = $this->prepareOrderData($user);
            $cdekInfo = $this->cdek->createOrder($orderData);
            Mail::to($user->email)->send(new SuccessPayment($user->name, $order->sum));
            $order->delivery_uuid = $cdekInfo['entity']['uuid'];
            $order->save();
            return response()->json([
                'success' => true
            ]);
        }
    }

    public function prepareOrderData(User $user)
    {
        $order = $user->orders()->where('status', 'pending')->first();
        $cartCount = $user->cart_items_count;

        return [
            'date' => now()->addDay()->format('Y-m-d\TH:i:sO'),
            'type' => 1,
            'number' => $order->uuid,
            'tariff_code' => 480,
            "shipment_point" => "SRK6",
            "delivery_point" => $order->delivery_point,
            'recipient' => [
                'name' => $user->name,
                'phones' => [['number' => $user->phone]],
                'email' => $user->email,
            ],
            'packages' => [
                'number' => '1',
                "weight" => 500 * $cartCount,
                "length" => 30,
                "width" => 20,
                "height" => 15,
                "items" => [
                    "name" => "Саженец",
                    "ware_key" => "Shazenec",
                    "payment" => [
                        "value" => $order->sum
                    ],
                    "cost" => $order->sum,
                    "weight" => 500,
                    "amount" => $cartCount
                ]
            ]
        ];
    }

}