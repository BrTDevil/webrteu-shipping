# Changelog

Toate modificările notabile ale acestui pachet sunt documentate aici.
Formatul urmează [Keep a Changelog](https://keepachangelog.com/ro/1.1.0/), iar versiunile [SemVer](https://semver.org/lang/ro/).

## [Unreleased]

### Adăugat
- Contractele `Carrier`, `HasPickupPoints`, `HasCourierOrder`, `HasBulkTracking`.
- DTO-uri readonly pentru adrese, colete, expedieri, AWB, cotații, servicii, tracking, puncte de ridicare.
- `ShippingManager` + facade `Shipping` (`carrier()`, `extend()`, `available()`).
- `Money`, `Normalizer`, `StatusResolver`, `CachedToken`.
- Tabelele `shipments` / `shipment_events`, modelele `Shipment` / `ShipmentEvent`.
- Comanda `shipping:sync`, job-ul `SyncShipmentStatus`, evenimentul `ShipmentStatusChanged`.
