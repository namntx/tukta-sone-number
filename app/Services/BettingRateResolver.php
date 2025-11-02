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
     * JSON format examples:
     * - "bac:de_dau" => {buy_rate: 0.7, payout: 80}
     * - "bac:xien:x2" => {buy_rate: 0.56, payout: 10}
     * - "bac:bao_lo:d2" => {buy_rate: 0.7, payout: 70}
     * - "bac:da_xien:c2" => {buy_rate: 0.7, payout: 600}
     * - "bac:de_duoi_4" => {buy_rate: 0.7, payout: 5000}
     * - "nam:bay_lo:d2" => {buy_rate: 0.7, payout: 70}
     */
    protected function loadFromJson(array $ratesJson, string $region): void
    {
        foreach ($ratesJson as $compositeKey => $data) {
            // Parse composite key: "region:type_code:modifiers"
            $parts = explode(':', $compositeKey);
            $rateRegion = $parts[0] ?? 'nam';

            // Only load rates for current region
            if ($rateRegion !== $region) continue;

            $typeCode = $parts[1] ?? null;
            if (!$typeCode) continue;

            // Map legacy type_code to canonical format
            // Legacy: de_dau, de_duoi, de_duoi_4
            // Canonical: dau, duoi (với digits nếu cần)
            $digits = null;
            $xienSize = null;
            $daiCount = null;

            // Xử lý đặc biệt cho các type_code có modifier trong tên
            if ($typeCode === 'de_duoi_4') {
                $typeCode = 'duoi';
                $digits = 4;
            } else {
                // Map các legacy type_code khác
                $typeCode = match($typeCode) {
                    'de_dau' => 'dau',
                    'de_duoi' => 'duoi',
                    default => $typeCode,
                };
            }

            // Parse modifiers từ các phần sau type_code (bắt đầu từ index 2)
            // Format: d2 (digits), x2 (xien_size), c1/c2 (dai_count)
            for ($i = 2; $i < count($parts); $i++) {
                $part = $parts[$i];
                if (str_starts_with($part, 'd') && strlen($part) > 1) {
                    // d2, d3, d4
                    $digits = (int)substr($part, 1);
                } elseif (str_starts_with($part, 'x') && strlen($part) > 1) {
                    // x2, x3, x4
                    $xienSize = (int)substr($part, 1);
                } elseif (str_starts_with($part, 'c') && strlen($part) > 1) {
                    // c1, c2, c3, c4
                    $daiCount = (int)substr($part, 1);
                }
            }

            // Build internal key để store trong map
            $k = self::key($typeCode, $digits, $xienSize, $daiCount);
            
            // Store rate với priority: customer override > region default > global
            // (nếu đã có trong map từ region default hoặc global, override bằng customer rate)
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

    /**
     * Get all rates for a customer and region, merging customer-specific overrides with defaults
     * Returns array of rate arrays with structure: ['type_code', 'digits', 'xien_size', 'dai_count', 'buy_rate', 'payout', 'is_default']
     */
    public function getAllForCustomerRegion(?int $customerId, string $region): array
    {
        // Build resolver to get merged rates
        $this->build($customerId, $region);

        // Also get default rates (customer_id = null)
        $defaultResolver = new self();
        $defaultResolver->build(null, $region);

        $result = [];

        // Collect all default rates
        foreach ($defaultResolver->map as $key => $rateData) {
            // Parse key: "type_code|digits|xien_size|dai_count"
            $parts = explode('|', $key);
            $typeCode = $parts[0] ?? null;
            $digits = ($parts[1] ?? '-') !== '-' ? (int)$parts[1] : null;
            $xienSize = ($parts[2] ?? '-') !== '-' ? (int)$parts[2] : null;
            $daiCount = ($parts[3] ?? '-') !== '-' ? (int)$parts[3] : null;

            // Check if customer has override
            $customerRate = null;
            if ($customerId && isset($this->map[$key])) {
                $customerRate = $this->map[$key];
            }

            $result[] = [
                'type_code' => $typeCode,
                'digits' => $digits,
                'xien_size' => $xienSize,
                'dai_count' => $daiCount,
                'buy_rate' => $customerRate ? (float)$customerRate['buy_rate'] : (float)$rateData['buy_rate'],
                'payout' => $customerRate ? (float)$customerRate['payout'] : (float)$rateData['payout'],
                'is_default' => $customerRate === null,
            ];
        }

        // Also include customer-only rates (not in defaults)
        if ($customerId) {
            foreach ($this->map as $key => $rateData) {
                if (!isset($defaultResolver->map[$key])) {
                    // Customer has custom rate not in defaults
                    $parts = explode('|', $key);
                    $typeCode = $parts[0] ?? null;
                    $digits = ($parts[1] ?? '-') !== '-' ? (int)$parts[1] : null;
                    $xienSize = ($parts[2] ?? '-') !== '-' ? (int)$parts[2] : null;
                    $daiCount = ($parts[3] ?? '-') !== '-' ? (int)$parts[3] : null;

                    $result[] = [
                        'type_code' => $typeCode,
                        'digits' => $digits,
                        'xien_size' => $xienSize,
                        'dai_count' => $daiCount,
                        'buy_rate' => (float)$rateData['buy_rate'],
                        'payout' => (float)$rateData['payout'],
                        'is_default' => false,
                    ];
                }
            }
        }

        return $result;
    }
}
