<?php

declare(strict_types=1);

return [

    /*
    | Curierul folosit de Shipping::carrier() fără argument.
    */
    'default' => env('SHIPPING_CARRIER', 'sameday'),

    /*
    | Sincronizarea statusurilor (curierii din România nu au webhook-uri de status).
    */
    'sync' => [
        'min_interval_minutes' => (int) env('SHIPPING_SYNC_MIN_INTERVAL', 60),
        'max_age_days' => (int) env('SHIPPING_SYNC_MAX_AGE_DAYS', 45),
        'batch_size' => 50,
        'queue' => env('SHIPPING_SYNC_QUEUE'),
    ],

    /*
    | Suprascrieri de mapare a statusurilor, per curier: ['fancourier' => ['S2' => 'delivered']].
    */
    'status_map' => [],

    'tables' => [
        'shipments' => 'shipments',
        'shipment_events' => 'shipment_events',
    ],

];
