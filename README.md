# webrteu/shipping

[![PHP](https://img.shields.io/badge/PHP-%5E8.2-777bb4)](https://www.php.net/)
[![Laravel](https://img.shields.io/badge/Laravel-12%E2%80%9313-ff2d20)](https://laravel.com/)
[![Licență MIT](https://img.shields.io/badge/licen%C8%9B%C4%83-MIT-green)](LICENSE.md)
[![Packagist](https://img.shields.io/packagist/v/webrteu/shipping)](https://packagist.org/packages/webrteu/shipping)

Nucleul comun pentru integrarea curierilor din România în Laravel: un contract unic (`Carrier`), DTO-uri
normalizate, statusuri unificate, tabelă de expedieri și sincronizare periodică a statusurilor. Curierii
propriu-ziși vin în pachete separate (`webrteu/sameday`, `webrteu/fancourier`, `webrteu/cargus`, `webrteu/gls`, `webrteu/dpd`).

Nu este un pachet oficial al vreunui curier.

## Cerințe

- PHP `^8.2`
- Laravel 12 sau 13
- cel puțin un pachet de curier instalat (altfel `Shipping::carrier()` aruncă `ShippingException`)

## Instalare

```bash
composer require webrteu/shipping
php artisan vendor:publish --tag=shipping-config
php artisan vendor:publish --tag=shipping-migrations
php artisan migrate
```

## Configurare

```dotenv
# Curierul implicit pentru Shipping::carrier() fără argument
SHIPPING_CARRIER=sameday
# Nu reverifica un AWB mai des de atât (minute)
SHIPPING_SYNC_MIN_INTERVAL=60
# Nu mai urmări expedierile mai vechi de atât (zile)
SHIPPING_SYNC_MAX_AGE_DAYS=45
# Coada pentru job-ul SyncShipmentStatus (gol = coada implicită)
SHIPPING_SYNC_QUEUE=
```

În `config/shipping.php` poți suprascrie maparea statusurilor unui curier:

```php
'status_map' => [
    'fancourier' => ['S2' => 'delivered'],
],
```

## Utilizare

```php
use WeBRTeu\Shipping\Data\AddressData;
use WeBRTeu\Shipping\Data\ParcelData;
use WeBRTeu\Shipping\Data\ShipmentData;
use WeBRTeu\Shipping\Facades\Shipping;
use WeBRTeu\Shipping\Models\Shipment;

$data = new ShipmentData(
    recipient: new AddressData(
        name: 'Ion Popescu',
        phone: '0721000000',
        county: 'Brașov',
        city: 'Brașov',
        street: 'Str. Lungă',
        number: '10',
    ),
    parcels: [new ParcelData(weightKg: 1.5)],
    reference: 'CMD-1001',
    codAmount: 14170, // 141,70 lei, mereu în bani
);

$awb = Shipping::carrier('fancourier')->createShipment($data);
$shipment = Shipment::recordFrom($awb, $data, $order); // $order = comanda din aplicație (morph `shippable`)

$pdf = Shipping::carrier('fancourier')->label($awb->awb);
```

Metode disponibile pe orice curier (`Carrier`):

| Metodă | Rezultat |
|---|---|
| `name()` | identificatorul curierului |
| `services()` | `Collection<ServiceData>` |
| `quote(ShipmentData)` | `QuoteData` (sumă în bani) |
| `createShipment(ShipmentData)` | `AwbData`. **Fără retry**: la timeout aruncă `ShipmentStateUnknownException` |
| `label(string $awb, LabelFormat)` | conținutul binar al etichetei (PDF A4/A6 sau ZPL) |
| `track(string $awb)` | `TrackingData` cu evenimentele în ordine cronologică |
| `cancel(string $awb)` | `bool` |

Interfețe opționale, după ce suportă API-ul curierului: `HasPickupPoints::pickupPoints($county, $city)`,
`HasCourierOrder::orderPickup(PickupRequestData)`, `HasBulkTracking::trackMany(array $awbs)`. Ce nu e suportat
aruncă `UnsupportedOperationException`.

```php
Shipping::carrier();       // curierul implicit din config
Shipping::available();     // ['sameday', 'fancourier', ...]
Shipping::extend('alt', fn ($app) => $app->make(AltCarrier::class)); // înregistrarea unui curier propriu
```

Utilitare: `Money::toDecimalString(14170)` → `"141.70"`, `Money::fromDecimal('141,70')` → `14170`,
`Normalizer::same('Municipiul Brașov', 'brasov')` → `true`, `Normalizer::phone('+40 721 000 000')` → `"0721000000"`.

## Sincronizarea statusurilor

Curierii din România nu trimit webhook-uri de status, așa că pachetul îi interoghează periodic:

```php
// routes/console.php
Schedule::command('shipping:sync')->hourly()->withoutOverlapping();
```

Ascultă schimbările în aplicație. Evenimentul se emite **doar la schimbare reală** de status:

```php
use WeBRTeu\Shipping\Events\ShipmentStatusChanged;
use WeBRTeu\Shipping\Enums\ShipmentStatus;

Event::listen(function (ShipmentStatusChanged $event) {
    if ($event->to === ShipmentStatus::Delivered) {
        $event->shipment->shippable?->markAsDelivered();
    }
});
```

Pentru un singur AWB, declanșat manual: `dispatch(new SyncShipmentStatus($shipment));`

## Comenzi Artisan

| Comandă | Ce face |
|---|---|
| `shipping:sync` | sincronizează expedierile nefinalizate, neverificate în ultimele `min_interval_minutes` |
| `shipping:sync --carrier=sameday` | doar pentru un curier |
| `shipping:sync --awb=123456` | un singur AWB, indiferent de ultima verificare |

O eroare la un AWB se loghează (`shipping.sync_failed`) și nu oprește restul lotului.

## Ce nu face pachetul

- nu vorbește direct cu niciun curier (asta fac pachetele de provider)
- nu face retry pe crearea AWB-urilor (risc de AWB dublat)
- nu calculează tarife proprii; `quote()` întoarce tariful curierului

## Testare

```bash
composer test
```

Rulează: `test:lint` (pint + rector), `test:spelling` (peck), `test:type-coverage` (100%), `test:types`
(phpstan nivel 8), `test:arch` și `test:unit` (Pest, coverage 100%, are nevoie de Xdebug sau PCOV).
Pe o mașină mai slabă: `composer test:unit:sequential`.

## Changelog

Vezi [CHANGELOG.md](CHANGELOG.md).

## Autor

[WeBRTeu](https://webrt.eu)

## Licență

MIT. Vezi [LICENSE.md](LICENSE.md).
