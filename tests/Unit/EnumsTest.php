<?php

declare(strict_types=1);

use WeBRTeu\Shipping\Enums\ShipmentStatus;

it('marks delivered, returned and cancelled as final', function (): void {
    expect(ShipmentStatus::finalStatuses())->toBe([
        ShipmentStatus::Delivered,
        ShipmentStatus::Returned,
        ShipmentStatus::Cancelled,
    ])->and(ShipmentStatus::InTransit->isFinal())->toBeFalse();
});

it('has a Romanian label for every status', function (ShipmentStatus $status): void {
    expect($status->label())->toBeString()->not->toBeEmpty();
})->with(ShipmentStatus::cases());
