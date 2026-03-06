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
        $subscription = $action->execute(
            CreateSubscriptionDTO::fromRequest($request)
        );

        return response()->json($subscription, 201);
    }
}
