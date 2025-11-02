<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\BettingRate;
use App\Services\BettingRateResolver;
use Illuminate\Validation\Rule;

class CustomerRateController extends Controller
{
    public function edit(Customer $customer)
    {
        $resolver = app(BettingRateResolver::class);
        $regions  = ['bac'=>'Miền Bắc','trung'=>'Miền Trung','nam'=>'Miền Nam'];

        $data = [];
        foreach (array_keys($regions) as $region) {
            // getAllForCustomerRegion now returns array directly
            $data[$region] = $resolver->getAllForCustomerRegion($customer->id, $region);
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

        // Load existing rates or initialize empty array
        $ratesJson = $customer->betting_rates ?? [];

        // Update rates in JSON structure
        foreach ($payload['items'] as $row) {
            $region = $row['region'];
            $typeCode = $row['type'];
            $digits = $row['digits'] ?? null;
            $xienSize = $row['xien'] ?? null;
            $daiCount = $row['dai'] ?? null;

            // Build composite key: "region:type_code:d2:x3:c4"
            $keyParts = [$region, $typeCode];
            if ($digits !== null) $keyParts[] = "d{$digits}";
            if ($xienSize !== null) $keyParts[] = "x{$xienSize}";
            if ($daiCount !== null) $keyParts[] = "c{$daiCount}";
            $compositeKey = implode(':', $keyParts);

            // Update rate in JSON
            $ratesJson[$compositeKey] = [
                'buy_rate' => (float)$row['buy'],
                'payout' => (float)$row['payout'],
            ];
        }

        // Save to customer's betting_rates JSON column
        $customer->betting_rates = $ratesJson;
        $customer->save();

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
