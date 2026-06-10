<?php

use App\Http\Controllers\Api\AiDescriptionController;
use App\Http\Controllers\Api\AiСonsultant;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\DadataController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\PaymentsController;
use Illuminate\Support\Facades\Route;

Route::get('/carts', [OrderController::class, 'createPayment']);
Route::prefix('assistant')->group(function () {
    Route::post('/chat', [AiСonsultant::class, 'sendMessage']);
    Route::post('/chat/stream', [AiСonsultant::class, 'streamMessage']);
});
Route::post('/forgot-password', [AuthController::class, 'sendResetLink']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/dadata/suggest', [DadataController::class, 'suggest']);
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{category}/products', [ProductController::class, 'getCategoryProducts']);
Route::get('/products/{product}', [ProductController::class, 'show']);
Route::get('/categories/{category}/products/all', [ProductController::class, 'getCategoryAllProducts']);
Route::middleware('jwt')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/me', [AuthController::class, 'update']);
    Route::post('/logout', [AuthController::class, 'logout']);
});

Route::group(['middleware' => 'jwt'], function () {
    Route::delete('cart', [CartController::Class, 'destroy']);

    Route::delete('cart/remove', [CartController::Class, 'destroyFromId']);
    Route::put('cart', [CartController::Class, 'update']);
    Route::put('user', [UserController::Class, 'update']);

    Route::resource('cart', CartController::class)->except('create', 'show', 'update', 'edit', 'destroy');

    Route::resource('products', ProductController::Class)->except('create');
    Route::resource('category', CategoryController::Class)->except('create');
    Route::post('/generate-description', [AiDescriptionController::class, 'generate']);
    Route::post('category/get-datatable', [CategoryController::Class, 'getDataTable']);
    Route::post('products/get-datatable', [ProductController::Class, 'getDataTable']);

    Route::get('order/cities', [OrderController::class, 'getCities']);
    Route::get('order/delivery-points', [OrderController::class, 'getDeliveryPoints']);
    Route::post('order/calculator', [OrderController::class, 'calculator']);
    Route::post('order/payment', [OrderController::class, 'success']);
    Route::post('order', [OrderController::class, 'create']);

    Route::get('/orders/{id}', [OrderController::class, 'getUserOrder']);
    Route::get('/orders', [OrderController::class, 'getUserOrders']);
    Route::get('/orders-paginated', [OrderController::class, 'getUserOrdersPaginated']);
    Route::get('order/success', [OrderController::class, 'success']);
    Route::post('payments', [PaymentsController::class, 'create']);

    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users/get-datatable', [UserController::class, 'getDataTable']);
    Route::get('/users/roles', [UserController::class, 'getRoles']);
    Route::post('/users', [UserController::class, 'store']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);
    Route::post('/users/{id}/reset-password', [UserController::class, 'resetPassword']);

    Route::post('/admin/orders/datatable', [OrderController::class, 'getAllOrdersForDataTable']);
    
    Route::get('/admin/orders', [OrderController::class, 'getAllOrders']);
    Route::get('/admin/orders/{id}', [OrderController::class, 'getOrderDetailsForAdmin']);
    Route::put('/admin/orders/{id}/status', [OrderController::class, 'updateOrderStatus']);
    Route::get('/admin/orders/statistics', [OrderController::class, 'getOrdersStatistics']);
});

