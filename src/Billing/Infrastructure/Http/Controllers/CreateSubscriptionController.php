<?php

declare(strict_types=1);

namespace Billing\Infrastructure\Http\Controllers;

use Billing\Infrastructure\Http\Requests\CreateSubscriptionHttpRequest;
use Billing\Application\Actions\CreateSubscription;
use Billing\Application\DTOs\CreateSubscriptionDTO;
use Illuminate\Http\JsonResponse;

class CreateSubscriptionController
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
