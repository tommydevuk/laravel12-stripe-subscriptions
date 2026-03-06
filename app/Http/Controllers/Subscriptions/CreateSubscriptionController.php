<?php

declare(strict_types=1);

namespace App\Http\Controllers\Subscriptions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subscriptions\CreateSubscriptionHttpRequest;
use Billing\Application\Actions\CreateSubscription;
use Billing\Application\DTOs\CreateSubscriptionDTO;
use Illuminate\Http\JsonResponse;

class CreateSubscriptionController extends Controller
{
    public function __invoke(
        CreateSubscriptionHttpRequest $request,
        CreateSubscription $action,
    ): JsonResponse {
        $subscription = $action->execute(new CreateSubscriptionDTO(
            customerId: $request->input('customer_id'),
            planId: $request->input('plan_id'),
            paymentMethodId: $request->input('payment_method_id'),
        ));

        return response()->json($subscription, 201);
    }
}
