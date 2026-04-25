<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DishController;
use App\Http\Controllers\Api\OrderCancellationController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\OrderStatusController;
use App\Http\Controllers\Api\WebhookController;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\IsAdmin;

Route::middleware('throttle:5,1')->post('/backend/login', [AuthController::class, 'login'])->name('backend.login');

Route::middleware('throttle:60,1')->get('/dishes', [DishController::class, 'index'])->name('dishes.index');
Route::middleware('throttle:10,1')->post('/orders', [OrderController::class, 'store'])->name('orders.store');

Route::post('/webhook/stripe', [WebhookController::class, 'handleWebhook'])->name('webhook.stripe');

Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    Route::post('/backend/logout', [AuthController::class, 'logout'])->name('backend.logout');

    Route::patch('/orders/{order}/status', [OrderStatusController::class, 'editStatus'])->name('orders.status.update');
    Route::post('/orders/{order}/cancel', OrderCancellationController::class)->name('orders.cancel');

    Route::post('/dishes/{dish}/availability', [DishController::class, 'toggleAvailability'])->name('dishes.availability');
    Route::get('/dishes/ordered', [OrderController::class, 'index'])->name('orders.ordered');
});

Route::middleware(['auth:sanctum', IsAdmin::class, 'throttle:60,1'])->group(function () {
    Route::post('/backend/register', [AuthController::class, 'register'])->name('backend.register');

    Route::get('/dishes/deleted', [DishController::class, 'showDeleted'])->name('dishes.deleted');
    Route::post('/dishes/{dish}/restore', [DishController::class, 'restore'])->withTrashed()->name('dishes.restore');
    Route::apiResource('dishes', DishController::class)->except('index');
});
