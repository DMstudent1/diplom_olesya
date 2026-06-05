<?php

use App\Http\Controllers\Api\AiDescriptionController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\DadataController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\PaymentsController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/dadata/suggest', [DadataController::class, 'suggest']);
Route::get('/categories/{category}/products', [ProductController::class, 'getCategoryProducts']);
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

    Route::get('order/delivery-points', [OrderController::class, 'getDeliveryPoints']);
    Route::post('order/calculator', [OrderController::class, 'calculator']);
    Route::post('payments', [PaymentsController::class, 'create']);
});