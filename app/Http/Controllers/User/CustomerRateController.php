<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\BettingRate;
use App\Services\BettingRateResolver;

class CustomerRateController extends Controller
{
    public function edit(Customer $customer)
    {
        $resolver = app(BettingRateResolver::class);
        $regions  = ['bac'=>'Miền Bắc','trung'=>'Miền Trung','nam'=>'Miền Nam'];

        $data = [];
        foreach (array_keys($regions) as $region) {
            $data[$region] = $resolver->getAllForCustomerRegion($customer->id, $region)
                ->map(function(BettingRate $r){
                    return [
                        'id'        => $r->id,
                        'type_code' => $r->type_code,
                        'digits'    => $r->digits,
                        'xien_size' => $r->xien_size,
                        'dai_count' => $r->dai_count,
                        'buy_rate'  => (float)$r->buy_rate,
                        'payout'    => (float)$r->payout,
                        'is_default'=> $r->customer_id === null,
                    ];
                })->toArray();
        }

        return view('user.customers.rates-edit', compact('customer','regions','data'));
    }

    public function update(Request $request, Customer $customer)
    {
        $regions = ['bac','trung','nam'];

        $payload = $request->validate([
            'items'            => ['required','array'],
            'items.*.region'   => ['required', Rule::in($regions)],
            'items.*.type'     => ['required','string','max:50'],
            'items.*.buy'      => ['required','numeric','between:0,100000'],
            'items.*.payout'   => ['required','numeric','between:0,10000000'],
            'items.*.digits'   => ['nullable','integer','between:1,4'],
            'items.*.xien'     => ['nullable','integer','between:2,4'],
            'items.*.dai'      => ['nullable','integer','between:1,4'],
        ]);

        foreach ($payload['items'] as $row) {
            BettingRate::updateOrCreate(
                [
                    'customer_id' => $customer->id,
                    'region'      => $row['region'],
                    'type_code'   => $row['type'],
                    'digits'      => $row['digits'] ?? null,
                    'xien_size'   => $row['xien'] ?? null,
                    'dai_count'   => $row['dai'] ?? null,
                ],
                [
                    'buy_rate'    => $row['buy'],
                    'payout'      => $row['payout'],
                    'is_active'   => true,
                ]
            );
        }

        return back()->with('status','Đã lưu giá cho khách.');
    }

    // Xóa 1 dòng giá riêng → fallback về default
    public function destroy(Customer $customer, BettingRate $rate)
    {
        abort_if($rate->customer_id !== $customer->id, 404);
        $rate->delete();
        return back()->with('status','Đã xóa giá riêng, dùng mặc định.');
    }
}
