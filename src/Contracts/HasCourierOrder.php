<?php

declare(strict_types=1);

namespace WeBRTeu\Shipping\Contracts;

use WeBRTeu\Shipping\Data\PickupRequestData;

/**
 * Comandă de ridicare de la sediu, pentru curierii la care AWB-ul nu e suficient.
 */
interface HasCourierOrder
{
    /**
     * @return string identificatorul comenzii de ridicare
     */
    public function orderPickup(PickupRequestData $request): string;
}
