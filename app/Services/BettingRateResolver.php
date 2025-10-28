<?php

namespace App\Services;

use App\Models\BettingRate;
use Illuminate\Support\Facades\Cache;

class BettingRateResolver
{
    /**
     * Lấy 1 rate (buy_rate, payout) theo ưu tiên:
     * customer-specific > default-by-region. Fallback: null nếu không có.
     *
     * @param  int|null     $customerId  cho phép null (khách vãng lai)
     * @param  string       $region      bac|trung|nam
     * @param  string       $typeCode    ví dụ: 'dau','duoi','de_duoi_4','bao_lo','xien','da_thang','da_xien','xiu_chu','bay_lo'
     * @param  array        $meta        ['digits'=>2,'xien_size'=>2,'dai_count'=>2] (tùy type)
     * @return array|null   ['buy_rate'=>float,'payout'=>float]
     */
    public function get(?int $customerId, string $region, string $typeCode, array $meta = []): ?array
    {
        $key = $this->cacheKey($customerId, $region, $typeCode, $meta);
        return Cache::remember($key, now()->addMinutes(10), function () use ($customerId, $region, $typeCode, $meta) {

            $base = BettingRate::query()
                ->active()
                ->region($region)
                ->type($typeCode)
                ->matchMeta($meta)
                ->orderByRaw('customer_id IS NULL') // ưu tiên có customer_id trước
                ->orderByDesc('customer_id');       // rồi mới default

            $rate = (clone $base)->when($customerId, fn($q)=>$q->where('customer_id', $customerId))
                                 ->first();

            if (!$rate) {
                $rate = (clone $base)->whereNull('customer_id')->first();
            }

            return $rate ? [
                'buy_rate' => (float)$rate->buy_rate,
                'payout'   => (float)$rate->payout,
            ] : null;
        });
    }

    public function getAllForCustomerRegion(?int $customerId, string $region)
    {
        // Dùng cho UI: gộp customer-specific đè lên default
        $defaults = BettingRate::active()->region($region)->whereNull('customer_id')->get();
        $customs  = $customerId
            ? BettingRate::active()->region($region)->where('customer_id',$customerId)->get()
            : collect();

        // Map theo signature
        $map = [];
        $put = function($r) use (&$map){
            $sig = self::signature($r->type_code, [
                'digits'=>$r->digits, 'xien_size'=>$r->xien_size, 'dai_count'=>$r->dai_count
            ]);
            $map[$sig] = $r;
        };

        foreach ($defaults as $r) $put($r);
        foreach ($customs as $r)  $put($r);

        return collect($map)->values();
    }

    public static function signature(string $typeCode, array $meta = []): string
    {
        return $typeCode.'|'.($meta['digits'] ?? '').'|'.($meta['xien_size'] ?? '').'|'.($meta['dai_count'] ?? '');
    }

    protected function cacheKey(?int $customerId, string $region, string $typeCode, array $meta): string
    {
        return 'rate:'.($customerId ?? 'none').":$region:".self::signature($typeCode,$meta);
    }
}
