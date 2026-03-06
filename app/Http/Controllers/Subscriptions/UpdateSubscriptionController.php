<?php

declare(strict_types=1);

namespace App\Http\Controllers\Subscriptions;

use App\Http\Requests\Subscriptions\UpdateSubscriptionHttpRequest;
use Billing\Application\Actions\UpdateSubscription;
use Billing\Application\DTOs\UpdateSubscriptionDTO;
use Illuminate\Http\JsonResponse;

final class UpdateSubscriptionController
{
    public function __invoke(
        string $subscriptionId,
        UpdateSubscriptionHttpRequest $request,
        UpdateSubscription $action
    ): JsonResponse {
        $action->execute(new UpdateSubscriptionDTO(
            gatewaySubscriptionId: $subscriptionId,
            newPlanId: $request->input('plan_id')
        ));

        return response()->json(['message' => 'Subscription update initiated.']);
    }
}
