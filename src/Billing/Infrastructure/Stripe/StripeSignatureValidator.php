<?php

declare(strict_types=1);

namespace Billing\Infrastructure\Stripe;

use Illuminate\Http\Request;
use Spatie\WebhookClient\SignatureValidator\SignatureValidator;
use Spatie\WebhookClient\WebhookConfig;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class StripeSignatureValidator implements SignatureValidator
{
    public function isValid(Request $request, WebhookConfig $config): bool
    {
        $signature = $request->header($config->signatureHeaderName);

        if (! is_string($signature)) {
            return false;
        }

        try {
            Webhook::constructEvent(
                (string) $request->getContent(),
                $signature,
                $config->signingSecret
            );
        } catch (SignatureVerificationException) {
            return false;
        }

        return true;
    }
}
