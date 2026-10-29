<?php

declare(strict_types=1);

namespace WeBRTeu\Shipping\Enums;

enum PickupPointType: string
{
    case Locker = 'locker';
    case Pudo = 'pudo';
    case Office = 'office';
}
