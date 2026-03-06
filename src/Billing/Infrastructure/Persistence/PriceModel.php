<?php

declare(strict_types=1);

namespace Billing\Infrastructure\Persistence;

use Billing\Infrastructure\Stripe\StripeSyncable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $stripe_id
 * @property int $product_id
 * @property int $amount
 * @property string $currency
 * @property string $type
 * @property string|null $interval
 * @property bool $active
 * @property array<string, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
final class PriceModel extends Model
{
    use StripeSyncable;

    protected $table = 'prices';

    protected $fillable = [
        'stripe_id',
        'product_id',
        'amount',
        'currency',
        'type',
        'interval',
        'active',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'integer',
        'active' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * @return BelongsTo<ProductModel, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductModel::class, 'product_id');
    }
}
