<?php

namespace App\Http\Controllers\Api;

use App\Mail\SuccessPayment;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
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
    
    public function getAllOrdersForDataTable(Request $request)
    {
        $query = Order::query()
            ->with('user')
            ->orderBy('created_at', 'desc');

            if ($request->has('search') && $request->search) {
            $search = $request->search;
            if (is_array($search)) {
                $search = $search['value'] ?? '';
            }
            
            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('uuid', 'LIKE', "%{$search}%")
                      ->orWhereHas('user', function ($userQuery) use ($search) {
                          $userQuery->where('name', 'LIKE', "%{$search}%")
                                    ->orWhere('email', 'LIKE', "%{$search}%");
                      });
                });
            }
        }
        $perPage = $request->input('length', 10);
        $start = $request->input('start', 0);
        $page = ($start / $perPage) + 1;

        $orders = $query->paginate($perPage, ['*'], 'page', $page);

        // Обновляем статусы для заказов
        foreach ($orders as $order) {
            $this->updateOrderStatusIfNeeded($order);
        }
        $data = [];
        foreach ($orders as $order) {
            $productIds = json_decode($order->products, true);
            $productsCount = is_array($productIds) ? count($productIds) : 0;

            $data[] = [
                'id' => $order->id,
                'uuid' => $order->uuid,
                'user' => [
                    'id' => $order->user->id ?? null,
                    'name' => $order->user->name ?? 'Пользователь удален',
                    'email' => $order->user->email ?? '-',
                    'phone' => $order->user->phone ?? '-',
                ],
                'status' => $order->status,
                'status_label' => $this->getStatusLabel($order->status),
                'status_color' => $this->getStatusColor($order->status),
                'sum' => (float) $order->sum,
                'products_count' => $productsCount,
                'delivery_point' => $order->delivery_point,
                'delivery_uuid' => $order->delivery_uuid,
                'payment_id' => $order->payment_id,
                'created_at' => $order->created_at ? $order->created_at->format('Y-m-d H:i:s') : null,
                'created_date' => $order->created_at ? $order->created_at->format('d.m.Y') : null,
                'updated_at' => $order->updated_at ? $order->updated_at->format('Y-m-d H:i:s') : null,
            ];
        }

        return response()->json([
            'draw' => intval($request->input('draw', 1)),
            'recordsTotal' => Order::count(),
            'recordsFiltered' => $orders->total(),
            'data' => $data,
        ]);
    }

     public function getAllOrders(Request $request)
    {
        // Проверяем права доступа
        $user = Auth::user();
        if (!$user || !$user->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Доступ запрещен'
            ], 403);
        }

        $perPage = $request->input('per_page', 15);
        $status = $request->input('status', null);
        $userId = $request->input('user_id', null);
        $fromDate = $request->input('from_date', null);
        $toDate = $request->input('to_date', null);

        $query = Order::with('user')->orderBy('created_at', 'desc');

        if ($fromDate) {
            $query->whereDate('created_at', '>=', $fromDate);
        }
        if ($toDate) {
            $query->whereDate('created_at', '<=', $toDate);
        }

        $orders = $query->paginate($perPage);

        // Обновляем статусы
        foreach ($orders as $order) {
            $this->updateOrderStatusIfNeeded($order);
        }

        return response()->json([
            'success' => true,
            'data' => $orders,
            'current_page' => $orders->currentPage(),
            'last_page' => $orders->lastPage(),
            'per_page' => $orders->perPage(),
            'total' => $orders->total(),
        ]);
    }
public function getOrderDetailsForAdmin($id)
{
    $order = Order::with('user')->findOrFail($id);
    
        $productIds = json_decode($order->products, true);

        $products = Product::whereIn('id', $productIds)->get();

    return response()->json([
        'success' => true,
        'order' => [
            'id' => $order->id,
            'uuid' => $order->uuid,
            'user' => $order->user,
            'products' => $products,
            'status' => $order->status,
            'sum' => (float) $order->sum,
            'delivery_point' => $order->delivery_point,
            'delivery_uuid' => $order->delivery_uuid,
            'payment_id' => $order->payment_id,
            'created_at' => $order->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $order->updated_at?->format('Y-m-d H:i:s'),
        ]
    ]);
}

    private function getStatusLabel($status)
    {
        $labels = [
            'pending' => 'Ожидает оплаты',
            'succeeded' => 'Оплачен',
            'canceled' => 'Отменен',
            'processing' => 'В обработке',
            'delivered' => 'Доставлен',
        ];
        return $labels[$status] ?? $status;
    }

    private function getStatusColor($status)
    {
        $colors = [
            'pending' => 'warning',
            'succeeded' => 'success',
            'canceled' => 'error',
            'processing' => 'info',
            'delivered' => 'primary',
        ];
        return $colors[$status] ?? 'default';
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