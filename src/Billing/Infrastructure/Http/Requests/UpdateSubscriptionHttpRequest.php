<?php

declare(strict_types=1);

namespace Billing\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateSubscriptionHttpRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'plan_id' => ['required', 'string'],
        ];
    }
}
