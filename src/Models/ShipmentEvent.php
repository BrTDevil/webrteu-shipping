<?php

declare(strict_types=1);

namespace WeBRTeu\Shipping\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use WeBRTeu\Shipping\Enums\ShipmentStatus;

/**
 * @property int $id
 * @property int $shipment_id
 * @property ShipmentStatus $status
 * @property string $code
 * @property string|null $label
 * @property string|null $location
 * @property Carbon|null $occurred_at
 * @property array<array-key, mixed>|null $raw
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ShipmentEvent extends Model
{
    protected $guarded = ['id'];

    public function getTable(): string
    {
        return Config::string('shipping.tables.shipment_events', 'shipment_events');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ShipmentStatus::class,
            'occurred_at' => 'datetime',
            'raw' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Shipment, $this>
     */
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class, 'shipment_id');
    }
}
