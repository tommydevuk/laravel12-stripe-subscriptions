<?php

declare(strict_types=1);

namespace Billing\Application\DTOs;

use App\Http\Requests\Subscriptions\CreateSubscriptionHttpRequest;

readonly class CreateSubscriptionDTO
{
    public function __construct(
        public string $customerId,
        public string $planId,
        public string $paymentMethodId,
    ) {}

    public static function fromRequest(CreateSubscriptionHttpRequest $request): self
    {
        return new self(
            customerId: (string) $request->input('customer_id'),
            planId: (string) $request->input('plan_id'),
            paymentMethodId: (string) $request->input('payment_method_id'),
        );
    }
}
