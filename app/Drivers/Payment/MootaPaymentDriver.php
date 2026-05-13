<?php

declare(strict_types=1);

namespace App\Drivers\Payment;

use App\Contract\PaymentDriverInterface;
use App\Data\PaymentData;
use App\Data\SalesOrderData;
use App\Data\SalesOrderItemData;
use App\Models\SalesOrder;
use App\Services\SalesOrderService;
use Illuminate\Support\Facades\Http;
use Spatie\LaravelData\DataCollection;

class MootaPaymentDriver implements PaymentDriverInterface
{
    public readonly string $driver;

    public function __construct() {
        $this->driver = 'moota';
    }

    /** @return DataCollection<PaymentData> */
    public function getMethods(): DataCollection
    {
        return PaymentData::collect([
            PaymentData::from([
                'driver' => $this->driver,
                'method' => 'bca-bank-transfer',
                'label' => '(Moota) Bank Transfer - BCA',
                'payload' => [
                    'account_id' => 'bpPkB9xrWB2'
                ]
            ])
        ], DataCollection::class);
    }

    public function process(SalesOrderData $sales_order)
    {
        $response = Http::withToken(config('services.moota.access_token'))
        ->withHeaders([
            'Accept' => 'application/json',
        ])
        ->post(
            'https://api.moota.co/api/v2/create-transaction',[
                'order_id' => $sales_order->trx_id,
                'account_id' => data_get($sales_order->payment->payload, 'account_id'),
                'customers' => [
                    'name' => $sales_order->customer->full_name,
                    'email' => $sales_order->customer->email,
                    'phone' => $sales_order->customer->phone
                ],
                'items' => $sales_order->items->toCollection()->map(function(SalesOrderItemData $item) {
                    return [
                        'name' => $item->name,
                        'description' => $item->short_desc,
                        'qty' => (int) $item->quantity,
                        'price' => (float) $item->price
                    ];
                })->merge([
                    [
                    'name' => $sales_order->shipping->courier,
                    'description' => $sales_order->shipping->estimated_delivery,
                    'qty' => 1,
                    'price' =>(float) $sales_order->shipping_cost
                    ]
                ])->toArray(),
                'description' => '',
                'note' => '',
                'redirect_url' => route('order-confirmed', $sales_order->trx_id),
                'total' =>(float) $sales_order->total,
            ]);

        if ($response->failed()) {
            throw new \Exception($response->body());
        }

        $responseData = $response->json();

        // Moota V2 biasanya mengembalikan URL di dalam key 'payment_url'
        // atau 'payment_link' tergantung jenis integrasinya.
        return app(SalesOrderService::class)->updateShippingPayload($sales_order, [
            'moota_payload' => $responseData
        ]);

        // return app(SalesOrderService::class)->updateShippingPayload($sales_order, [
        //     'moota_payload' => $response->json('data')
        // ]);

    }

    public function shouldShowPayNowButton(SalesOrderData $sales_order): bool
    {
        return true;
    }

    public function getRedirectUrl(SalesOrderData $sales_order) : ?string
    {
        // return data_get($sales_order->payment->payload, 'moota_payload.payment_url');
        // dd($sales_order->payment->payload);
        // return data_get($sales_order->payment->payload, 'moota_payload.payment_url')
        //     ?? data_get($sales_order->payment->payload, 'moota_payload.payment_link');

        $payload = $sales_order->payment->payload;

        return data_get($payload, 'moota_payload.data.payment_url')
            ?? data_get($payload, 'moota_payload.data.qr_url')
            ?? data_get($payload, 'moota_payload.payment_url'); // Fallback jika struktur flat
    }
}
