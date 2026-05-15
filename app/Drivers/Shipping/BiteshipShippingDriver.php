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

    // public function getServices(): DataCollection
    // {
    //     // Di Biteship, kodenya biasanya gabungan kurir & service
    //     return ShippingServiceData::collect([
    //         ['driver' => $this->driver, 'code' => 'jne_reg', 'courier' => 'jne', 'service' => 'reg'],
    //         ['driver' => $this->driver, 'code' => 'sicepat_reg', 'courier' => 'sicepat', 'service' => 'reg'],
    //         ['driver' => $this->driver, 'code' => 'gojek_instant', 'courier' => 'gojek', 'service' => 'instant'],
    //     ], DataCollection::class);
    // }

    public function getServices(): DataCollection
    {
        return ShippingServiceData::collect([
            // 'service' harus cocok dengan 'courier_service_code' dari Biteship
            ['driver' => $this->driver, 'code' => 'jne_reg', 'courier' => 'jne', 'service' => 'reg'],
            ['driver' => $this->driver, 'code' => 'jne_yes', 'courier' => 'jne', 'service' => 'yes'],
            ['driver' => $this->driver, 'code' => 'jne_jtr', 'courier' => 'jne', 'service' => 'jtr'],
        ], DataCollection::class);
    }

    public function getRate(
    RegionData $origin,
    RegionData $destination,
    CartData $cart,
    ShippingServiceData $shipping_service
    ): ?ShippingData {

        $response = Http::withToken(config('shipping.biteship.api_key'))
            ->post('https://api.biteship.com/v1/rates/couriers', [
                'origin_postal_code' => (int) $origin->postal_code,
                'destination_postal_code' => (int) $destination->postal_code,
                'couriers' => strtolower($shipping_service->courier), // Biteship lebih aman dengan lowercase
                'items' => [
                    [
                        'name' => 'Cart Items',
                        'value' => (int) $cart->total,
                        'weight' => (int) $cart->total_weight,
                        'quantity' => 1
                    ]
                ]
            ]);

        if ($response->failed()) {
            return null;
        }

        $pricingData = $response->json('pricing');
// dd($pricingData);

        $selectedService = collect($pricingData)->first(function ($item) use ($shipping_service) {
            // Cek apakah kurirnya sama (misal: jne)
            // DAN cek apakah kodenya sama (misal: reg, jtr, atau yes)
            return strtolower($item['courier_code']) === strtolower($shipping_service->courier) &&
                strtolower($item['courier_service_code']) === strtolower($shipping_service->service);
        });

        if (!$selectedService) {
            return null;
        }

        return new ShippingData(
            driver: $this->driver,
            courier: $selectedService['courier_name'], // "JNE"
            service: $selectedService['courier_service_name'], // "Reguler"
            estimated_delivery: $selectedService['duration'], // "1 - 2 days"
            cost: (float) $selectedService['price'],
            weight: (int) $cart->total_weight,
            origin: $origin,
            destination: $destination,
            logo_url: $selectedService['courier_logo'] ?? '' // Pakai logo dari Biteship
        );
    }
}
