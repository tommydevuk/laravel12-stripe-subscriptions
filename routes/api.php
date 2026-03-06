<?php

use App\Http\Controllers\Subscriptions\CreateSubscriptionController;
use App\Http\Controllers\Subscriptions\UpdateSubscriptionController;
use App\Http\Controllers\Subscriptions\RetryPaymentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->prefix('subscriptions')->group(function () {
    Route::post('/', CreateSubscriptionController::class);
    Route::patch('/{subscription_id}', UpdateSubscriptionController::class);
    Route::post('/retry', RetryPaymentController::class);
});

Route::webhooks('stripe-webhooks');
