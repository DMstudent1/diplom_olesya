<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

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

    Route::resource('cart', CartController::class)->except('create', 'show', 'update', 'edit', 'destroy');

    Route::resource('products', ProductController::Class)->except('create');
    Route::resource('category', CategoryController::Class)->except('create');
    Route::post('category/get-datatable', [CategoryController::Class, 'getDataTable']);
    Route::post('products/get-datatable', [ProductController::Class, 'getDataTable']);
});