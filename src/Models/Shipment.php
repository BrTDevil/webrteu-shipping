<?php

declare(strict_types=1);

namespace WeBRTeu\Shipping\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use WeBRTeu\Shipping\Data\AwbData;
use WeBRTeu\Shipping\Data\ShipmentData;
use WeBRTeu\Shipping\Data\TrackingData;
use WeBRTeu\Shipping\Enums\ShipmentStatus;
use WeBRTeu\Shipping\Events\ShipmentStatusChanged;

/**
 * @property int $id
 * @property string $carrier
 * @property string $awb
 * @property string|null $reference
 * @property string|null $shippable_type
 * @property int|string|null $shippable_id
 * @property ShipmentStatus $status
 * @property string|null $status_label
 * @property int|null $cost
 * @property int $cod_amount
 * @property Carbon|null $last_tracked_at
 * @property Carbon|null $delivered_at
 * @property array<array-key, mixed>|null $raw
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Shipment extends Model
{
    protected $guarded = ['id'];

    public function getTable(): string
    {
        return Config::string('shipping.tables.shipments', 'shipments');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ShipmentStatus::class,
            'cost' => 'integer',
            'cod_amount' => 'integer',
            'last_tracked_at' => 'datetime',
            'delivered_at' => 'datetime',
            'raw' => 'array',
        ];
    }

    /**
     * @return HasMany<ShipmentEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(ShipmentEvent::class, 'shipment_id');
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function shippable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Expedieri în stare nefinală, mai noi de `shipping.sync.max_age_days`.
     *
     * @param  Builder<self>  $query
     */
    public function scopePending(Builder $query): void
    {
        $query
            ->whereNotIn('status', array_map(fn (ShipmentStatus $status): string => $status->value, ShipmentStatus::finalStatuses()))
            ->where('created_at', '>=', now()->subDays(Config::integer('shipping.sync.max_age_days', 45)));
    }

    public static function recordFrom(AwbData $awb, ShipmentData $data, ?Model $shippable = null): self
    {
        $shipment = new self([
            'carrier' => $awb->carrier,
            'awb' => $awb->awb,
            'reference' => $data->reference,
            'status' => ShipmentStatus::Created,
            'status_label' => ShipmentStatus::Created->label(),
            'cost' => $awb->cost,
            'cod_amount' => $data->codAmount,
            'raw' => $awb->raw,
        ]);

        if ($shippable instanceof Model) {
            $shipment->shippable()->associate($shippable);
        }

        $shipment->save();

        return $shipment;
    }

    /**
     * Salvează evenimentele noi și statusul curent. Emite ShipmentStatusChanged doar la schimbare reală.
     */
    public function applyTracking(TrackingData $tracking): bool
    {
        $from = $this->status;

        DB::connection($this->getConnectionName())->transaction(function () use ($tracking): void {
            foreach ($tracking->events as $event) {
                $this->events()->firstOrCreate([
                    'code' => $event->code,
                    'occurred_at' => $event->occurredAt,
                ], [
                    'status' => $event->status,
                    'label' => $event->label,
                    'location' => $event->location,
                    'raw' => $event->raw,
                ]);
            }

            $this->forceFill([
                'status' => $tracking->status,
                'status_label' => $tracking->statusLabel,
                'last_tracked_at' => CarbonImmutable::now(),
                'delivered_at' => $tracking->deliveredAt ?? $this->delivered_at,
            ])->save();
        });

        if ($from === $tracking->status) {
            return false;
        }

        Event::dispatch(new ShipmentStatusChanged($this, $from, $tracking->status));

        return true;
    }
}
