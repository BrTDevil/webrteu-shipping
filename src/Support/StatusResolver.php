<?php

declare(strict_types=1);

namespace WeBRTeu\Shipping\Support;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use WeBRTeu\Shipping\Enums\ShipmentStatus;

final class StatusResolver
{
    /**
     * Cuvinte cheie căutate în eticheta statusului, în ordinea priorității.
     */
    private const KEYWORDS = [
        'anulat' => ShipmentStatus::Cancelled,
        'retur' => ShipmentStatus::Returning,
        'in livrare' => ShipmentStatus::OutForDelivery,
        'la curier' => ShipmentStatus::OutForDelivery,
        'livrat' => ShipmentStatus::Delivered,
        'easybox' => ShipmentStatus::AtPickupPoint,
        'locker' => ShipmentStatus::AtPickupPoint,
        'punct' => ShipmentStatus::AtPickupPoint,
        'tranzit' => ShipmentStatus::InTransit,
        'ridicat' => ShipmentStatus::PickedUp,
        'preluat' => ShipmentStatus::PickedUp,
        'inregistrat' => ShipmentStatus::Created,
    ];

    /**
     * @param  array<array-key, ShipmentStatus>  $builtInMap
     */
    public static function resolve(string $carrier, string $code, string $label, array $builtInMap = []): ShipmentStatus
    {
        $override = Config::get("shipping.status_map.{$carrier}.{$code}");

        if ($override instanceof ShipmentStatus) {
            return $override;
        }

        if (is_string($override) && ($status = ShipmentStatus::tryFrom($override)) instanceof ShipmentStatus) {
            return $status;
        }

        if (isset($builtInMap[$code])) {
            return $builtInMap[$code];
        }

        $haystack = strtolower(Normalizer::ascii($label));

        foreach (self::KEYWORDS as $keyword => $status) {
            if (str_contains($haystack, $keyword)) {
                return $status;
            }
        }

        Log::warning('shipping.unknown_status', [
            'carrier' => $carrier,
            'code' => $code,
            'label' => $label,
        ]);

        return ShipmentStatus::Unknown;
    }
}
