<?php

declare(strict_types=1);

namespace WeBRTeu\Shipping\Data;

use Carbon\CarbonImmutable;
use WeBRTeu\Shipping\Enums\ShipmentStatus;

final readonly class TrackingData
{
    /**
     * @param  list<TrackingEventData>  $events  în ordine cronologică
     * @param  array<array-key, mixed>  $raw
     */
    public function __construct(
        public string $awb,
        public ShipmentStatus $status,
        public string $statusLabel,
        public array $events = [],
        public ?CarbonImmutable $deliveredAt = null,
        public array $raw = [],
    ) {}

    /**
     * Sortează evenimentele cronologic; statusul e cel al ultimului eveniment.
     *
     * @param  list<TrackingEventData>  $events
     * @param  array<array-key, mixed>  $raw
     */
    public static function fromEvents(string $awb, array $events, array $raw = []): self
    {
        usort($events, fn (TrackingEventData $a, TrackingEventData $b): int => ($a->occurredAt?->getTimestamp() ?? 0) <=> ($b->occurredAt?->getTimestamp() ?? 0));

        $last = $events === [] ? null : $events[array_key_last($events)];
        $deliveredAt = null;

        foreach ($events as $event) {
            if ($event->status === ShipmentStatus::Delivered) {
                $deliveredAt = $event->occurredAt;
            }
        }

        return new self(
            awb: $awb,
            status: $last instanceof TrackingEventData ? $last->status : ShipmentStatus::Unknown,
            statusLabel: $last instanceof TrackingEventData ? $last->label : ShipmentStatus::Unknown->label(),
            events: $events,
            deliveredAt: $deliveredAt,
            raw: $raw,
        );
    }

    public function lastEvent(): ?TrackingEventData
    {
        return $this->events === [] ? null : $this->events[array_key_last($this->events)];
    }
}
