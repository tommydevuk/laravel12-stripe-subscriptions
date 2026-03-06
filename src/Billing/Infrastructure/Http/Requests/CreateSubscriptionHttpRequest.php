<?php

declare(strict_types=1);

namespace Billing\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateSubscriptionHttpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'string'],
            'plan_id' => ['required', 'string'],
            'payment_method_id' => ['required', 'string'],
        ];
    }
}
