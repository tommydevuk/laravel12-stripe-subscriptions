<?php

declare(strict_types=1);

namespace Billing\Infrastructure\Http\Controllers;

use Billing\Application\Actions\RetryPayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class RetryPaymentController
{
    public function __invoke(
        Request $request,
        RetryPayment $action
    ): JsonResponse {
        $action->execute($request->input('invoice_id'));

        return response()->json(['message' => 'Payment retry attempted.']);
    }
}
