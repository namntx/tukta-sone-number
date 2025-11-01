<?php

namespace App\Services;

use App\Models\BettingRate;
use App\Models\Customer;

class BettingRateResolver
{
    /** @var array<string,array{buy_rate:float,payout:float}> */
    protected array $map = [];
    protected ?int $customerId = null;
    protected string $region = 'nam';

    public static function key(string $type, ?int $digits, ?int $xienSize, ?int $daiCount): string
    {
        return implode('|', [
            $type,
            is_null($digits)    ? '-' : (string)$digits,
            is_null($xienSize)  ? '-' : (string)$xienSize,
            is_null($daiCount)  ? '-' : (string)$daiCount,
        ]);
    }

    public function build(?int $customerId, string $region): self
    {
        $this->map = [];
        $this->customerId = $customerId;
        $this->region = $region;

        // GLOBAL (*)
        $globals = BettingRate::query()
            ->whereNull('customer_id')
            ->where(function($q){ $q->whereNull('region')->orWhere('region','*'); })
            ->get();

        foreach ($globals as $r) {
            $k = self::key($r->type_code, $r->digits, $r->xien_size, $r->dai_count);
            $this->map[$k] = ['buy_rate'=>(float)$r->buy_rate, 'payout'=>(float)$r->payout];
        }

        // REGION DEFAULT
        $byRegion = BettingRate::query()
            ->whereNull('customer_id')
            ->where('region', $region)
            ->get();

        foreach ($byRegion as $r) {
            $k = self::key($r->type_code, $r->digits, $r->xien_size, $r->dai_count);
            $this->map[$k] = ['buy_rate'=>(float)$r->buy_rate, 'payout'=>(float)$r->payout];
        }

        // CUSTOMER OVERRIDE - Check JSON column first, fallback to table
        if ($customerId) {
            $customer = Customer::find($customerId);

            if ($customer && !empty($customer->betting_rates)) {
                // NEW: Load from JSON column
                $this->loadFromJson($customer->betting_rates, $region);
            } else {
                // FALLBACK: Load from betting_rates table (backward compatibility)
                $byCustomer = BettingRate::query()
                    ->where('customer_id', $customerId)
                    ->where('region', $region)
                    ->get();

                foreach ($byCustomer as $r) {
                    $k = self::key($r->type_code, $r->digits, $r->xien_size, $r->dai_count);
                    $this->map[$k] = ['buy_rate'=>(float)$r->buy_rate, 'payout'=>(float)$r->payout];
                }
            }
        }

        return $this;
    }

    /**
     * Load rates from JSON structure
     * JSON format: "region:type_code:d2:x3:c4" => {buy_rate: 0.95, payout: 80}
     */
    protected function loadFromJson(array $ratesJson, string $region): void
    {
        foreach ($ratesJson as $compositeKey => $data) {
            // Parse composite key: "region:type_code:d2:x3:c4"
            $parts = explode(':', $compositeKey);
            $rateRegion = $parts[0] ?? 'nam';

            // Only load rates for current region
            if ($rateRegion !== $region) continue;

            $typeCode = $parts[1] ?? null;
            if (!$typeCode) continue;

            $digits = null;
            $xienSize = null;
            $daiCount = null;

            // Parse modifiers
            for ($i = 2; $i < count($parts); $i++) {
                $part = $parts[$i];
                if (str_starts_with($part, 'd')) {
                    $digits = (int)substr($part, 1);
                } elseif (str_starts_with($part, 'x')) {
                    $xienSize = (int)substr($part, 1);
                } elseif (str_starts_with($part, 'c')) {
                    $daiCount = (int)substr($part, 1);
                }
            }

            $k = self::key($typeCode, $digits, $xienSize, $daiCount);
            $this->map[$k] = [
                'buy_rate' => (float)($data['buy_rate'] ?? 1.0),
                'payout' => (float)($data['payout'] ?? 0.0),
            ];
        }
    }

    public function resolve(string $typeCode, ?int $digits=null, ?int $xienSize=null, ?int $daiCount=null): array
    {
        $typeCandidates = [$typeCode];
        if ($typeCode === 'xien' && $xienSize) {
            $typeCandidates[] = 'xi'.$xienSize; // hỗ trợ data kiểu xi2/xi3/xi4
        }

        $combos = [
            [$digits, $xienSize, $daiCount],
            [$digits, $xienSize, null],
            [$digits, null,      $daiCount],
            [$digits, null,      null],
            [null,    $xienSize, $daiCount],
            [null,    $xienSize, null],
            [null,    null,      $daiCount],
            [null,    null,      null],
        ];

        foreach ($typeCandidates as $t) {
            foreach ($combos as [$dg,$xs,$dc]) {
                $k = self::key($t, $dg, $xs, $dc);
                if (isset($this->map[$k])) {
                    return [$this->map[$k]['buy_rate'], $this->map[$k]['payout']];
                }
            }
        }

        return [1.0, 0.0];
    }
}
