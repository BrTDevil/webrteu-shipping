<?php

declare(strict_types=1);

namespace WeBRTeu\Shipping\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Throwable;
use WeBRTeu\Shipping\Contracts\Carrier;
use WeBRTeu\Shipping\Contracts\HasBulkTracking;
use WeBRTeu\Shipping\Data\TrackingData;
use WeBRTeu\Shipping\Models\Shipment;
use WeBRTeu\Shipping\ShippingManager;

class SyncShipmentsCommand extends Command
{
    protected $signature = 'shipping:sync
        {--carrier= : Doar expedierile acestui curier}
        {--awb= : Un singur AWB, sincronizat indiferent de ultima verificare}';

    protected $description = 'Sincronizează statusul expedierilor nefinalizate de la curieri';

    private int $checked = 0;

    private int $changed = 0;

    private int $failed = 0;

    public function handle(ShippingManager $shipping): int
    {
        $shipments = $this->query()->get();

        foreach (array_unique($shipments->map(fn (Shipment $shipment): string => $shipment->carrier)->all()) as $name) {
            $this->syncCarrier($shipping, $name, $shipments->where('carrier', $name)->values());
        }

        $this->info("Verificate: {$this->checked}, schimbate: {$this->changed}, erori: {$this->failed}.");

        return self::SUCCESS;
    }

    /**
     * @param  Collection<int, Shipment>  $shipments
     */
    private function syncCarrier(ShippingManager $shipping, string $name, Collection $shipments): void
    {
        if (! $shipping->has($name)) {
            $this->warn("Curierul [{$name}] nu este înregistrat; sar peste {$shipments->count()} expedieri.");
            $this->failed += $shipments->count();

            return;
        }

        $carrier = $shipping->carrier($name);

        foreach ($shipments->chunk(max(1, Config::integer('shipping.sync.batch_size', 50))) as $batch) {
            $this->syncBatch($carrier, $batch->values());
        }
    }

    /**
     * @return Builder<Shipment>
     */
    private function query(): Builder
    {
        $carrier = $this->option('carrier');
        $awb = $this->option('awb');

        $query = Shipment::query()
            ->when(is_string($carrier) && $carrier !== '', fn (Builder $query): Builder => $query->where('carrier', $carrier));

        if (is_string($awb) && $awb !== '') {
            return $query->where('awb', $awb);
        }

        $threshold = now()->subMinutes(Config::integer('shipping.sync.min_interval_minutes', 60));

        return $query
            ->pending()
            ->where(fn (Builder $query): Builder => $query->whereNull('last_tracked_at')->orWhere('last_tracked_at', '<', $threshold))
            ->orderBy('id');
    }

    /**
     * @param  Collection<int, Shipment>  $batch
     */
    private function syncBatch(Carrier $carrier, Collection $batch): void
    {
        if ($carrier instanceof HasBulkTracking) {
            try {
                $results = $carrier->trackMany($this->awbs($batch));
            } catch (Throwable $exception) {
                $this->recordFailure($carrier, implode(',', $this->awbs($batch)), $exception, $batch->count());

                return;
            }

            foreach ($batch as $shipment) {
                $tracking = $results->get($shipment->awb);

                if ($tracking instanceof TrackingData) {
                    $this->apply($carrier, $shipment, fn (): TrackingData => $tracking);
                }
            }

            return;
        }

        foreach ($batch as $shipment) {
            $this->apply($carrier, $shipment, fn (): TrackingData => $carrier->track($shipment->awb));
        }
    }

    /**
     * @param  Collection<int, Shipment>  $batch
     * @return list<string>
     */
    private function awbs(Collection $batch): array
    {
        return array_values($batch->map(fn (Shipment $shipment): string => $shipment->awb)->all());
    }

    /**
     * @param  \Closure(): TrackingData  $tracking
     */
    private function apply(Carrier $carrier, Shipment $shipment, \Closure $tracking): void
    {
        try {
            $this->checked++;

            if ($shipment->applyTracking($tracking())) {
                $this->changed++;
            }
        } catch (Throwable $exception) {
            $this->recordFailure($carrier, $shipment->awb, $exception);
        }
    }

    private function recordFailure(Carrier $carrier, string $awb, Throwable $exception, int $count = 1): void
    {
        $this->failed += $count;

        Log::error('shipping.sync_failed', [
            'carrier' => $carrier->name(),
            'awb' => $awb,
            'error' => $exception->getMessage(),
        ]);
    }
}
