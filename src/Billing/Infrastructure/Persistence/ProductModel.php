<?php

declare(strict_types=1);

namespace Billing\Infrastructure\Persistence;

use Billing\Infrastructure\Stripe\StripeSyncable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $stripe_id
 * @property string $name
 * @property string|null $description
 * @property bool $active
 * @property array<string, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
final class ProductModel extends Model
{
    use StripeSyncable;

    protected $table = 'products';

    protected $fillable = [
        'stripe_id',
        'name',
        'description',
        'active',
        'metadata',
    ];

    protected $casts = [
        'active' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * @return HasMany<PriceModel, $this>
     */
    public function prices(): HasMany
    {
        return $this->hasMany(PriceModel::class, 'product_id');
    }
}
