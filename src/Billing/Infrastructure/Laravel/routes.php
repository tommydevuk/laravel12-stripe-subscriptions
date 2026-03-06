<?php

use Billing\Infrastructure\Http\Controllers\CreateSubscriptionController;
use Billing\Infrastructure\Http\Controllers\RetryPaymentController;
use Billing\Infrastructure\Http\Controllers\UpdateSubscriptionController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('subscriptions')->group(function () {
    Route::post('/', CreateSubscriptionController::class);
    Route::patch('/{subscription_id}', UpdateSubscriptionController::class);
    Route::post('/retry', RetryPaymentController::class);
});

Route::webhooks('stripe-webhooks');
