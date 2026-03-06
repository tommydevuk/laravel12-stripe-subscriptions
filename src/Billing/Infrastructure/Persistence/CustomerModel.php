<?php

declare(strict_types=1);

namespace Billing\Infrastructure\Persistence;

use Billing\Infrastructure\Stripe\StripeSyncable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $stripe_id
 * @property string|null $name
 * @property string|null $email
 * @property array<string, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
final class CustomerModel extends Model
{
    use StripeSyncable;

    protected $table = 'customers';

    protected $fillable = [
        'stripe_id',
        'name',
        'email',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];
}
