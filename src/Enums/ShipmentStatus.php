<?php

declare(strict_types=1);

namespace WeBRTeu\Shipping\Enums;

enum ShipmentStatus: string
{
    case Created = 'created';
    case PickedUp = 'picked_up';
    case InTransit = 'in_transit';
    case OutForDelivery = 'out_for_delivery';
    case AtPickupPoint = 'at_pickup_point';
    case Delivered = 'delivered';
    case Returning = 'returning';
    case Returned = 'returned';
    case Cancelled = 'cancelled';
    case Exception = 'exception';
    case Unknown = 'unknown';

    public function isFinal(): bool
    {
        return in_array($this, [self::Delivered, self::Returned, self::Cancelled], true);
    }

    /**
     * @return list<self>
     */
    public static function finalStatuses(): array
    {
        return array_values(array_filter(self::cases(), fn (self $status): bool => $status->isFinal()));
    }

    public function label(): string
    {
        return match ($this) {
            self::Created => 'AWB generat',
            self::PickedUp => 'Preluat de curier',
            self::InTransit => 'În tranzit',
            self::OutForDelivery => 'În livrare',
            self::AtPickupPoint => 'În punctul de ridicare',
            self::Delivered => 'Livrat',
            self::Returning => 'În curs de returnare',
            self::Returned => 'Returnat',
            self::Cancelled => 'Anulat',
            self::Exception => 'Problemă la livrare',
            self::Unknown => 'Status necunoscut',
        };
    }
}
