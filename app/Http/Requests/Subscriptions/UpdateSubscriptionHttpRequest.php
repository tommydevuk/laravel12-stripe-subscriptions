<?php

declare(strict_types=1);

namespace App\Http\Requests\Subscriptions;

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
