<?php

declare(strict_types=1);

namespace App\Drivers\Shipping;

use App\Contract\ShippingDriverInterface;
use App\Data\CartData;
use App\Data\RegionData;
use App\Data\ShippingData;
use App\Data\ShippingServiceData;
use Illuminate\Support\Facades\Http;
use Spatie\LaravelData\DataCollection;

class BiteshipShippingDriver implements ShippingDriverInterface
{
    public readonly string $driver;

    public function __construct()
    {
        $this->driver = 'biteship';
    }

    public function getServices(): DataCollection
    {
        // Di Biteship, kodenya biasanya gabungan kurir & service
        return ShippingServiceData::collect([
            ['driver' => $this->driver, 'code' => 'jne_reg', 'courier' => 'jne', 'service' => 'reg'],
            ['driver' => $this->driver, 'code' => 'sicepat_reg', 'courier' => 'sicepat', 'service' => 'reg'],
            ['driver' => $this->driver, 'code' => 'gojek_instant', 'courier' => 'gojek', 'service' => 'instant'],
        ], DataCollection::class);
    }

    public function getRate(
        RegionData $origin,
        RegionData $destination,
        CartData $cart,
        ShippingServiceData $shipping_service
    ): ?ShippingData {

        // PERUBAHAN: Menggunakan Bearer Token & Struktur Payload Baru
        $response = Http::withToken(config('shipping.biteship.api_key'))
            ->post('https://api.biteship.com/v1/rates/couriers', [
                'origin_postal_code' => (int) $origin->postal_code,
                'destination_postal_code' => (int) $destination->postal_code,
                'couriers' => $shipping_service->courier,
                'items' => [
                    [
                        'name' => 'Cart Items',
                        'value' => $cart->total,
                        'weight' => $cart->total_weight,
                        'quantity' => 1 // Asumsi disederhanakan
                    ]
                ]
            ]);

dd($response);
        $data = $response->json('pricing');
dd($data);
        // PERUBAHAN: Cara mencari service yang sesuai
        $selectedService = collect($data)->first(function ($item) use ($shipping_service) {
            return $item['service_type'] === $shipping_service->service;
        });

        if (!$selectedService) {
            return null;
        }

        $est = $selectedService['shipment_duration_range'] . ' ' . $selectedService['shipment_duration_unit'];

        return new ShippingData(
            // driver: $this->driver,
            // courier: $selectedService['courier_name'],
            // service: $selectedService['service_display_name'],
            // estimation: $selectedService['shipment_duration_range'] . ' ' . $selectedService['shipment_duration_unit'],
            // price: (float) $selectedService['price'],
            // weight: $cart->total_weight,
            // origin: $origin,
            // destination: $destination,
            // logo_url: $selectedService['courier_logo'] // Biteship memberikan Logo!
            $this->driver,
            $shipping_service->courier,
            $shipping_service->service,
            $est,
            data_get($data, 'price'),
            data_get($data, 'weight'),
            $origin,
            $destination,
            data_get($data, 'logoUrl')
        );
    }
}
