<?php

declare(strict_types=1);

namespace Billing\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $customer_id
 * @property string $plan_id
 * @property string $status
 * @property string $gateway_id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class SubscriptionModel extends Model
{
    protected $table = 'subscriptions';

    protected $fillable = [
        'customer_id',
        'plan_id',
        'status',
        'gateway_id',
    ];

    /**
     * @return BelongsTo<PriceModel, $this>
     */
    public function price(): BelongsTo
    {
        return $this->belongsTo(PriceModel::class, 'plan_id', 'stripe_id');
    }
}
