<?php

namespace App\Services;

use App\Models\BettingRate;

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

        // CUSTOMER OVERRIDE
        if ($customerId) {
            $byCustomer = BettingRate::query()
                ->where('customer_id', $customerId)
                ->where('region', $region)
                ->get();

            foreach ($byCustomer as $r) {
                $k = self::key($r->type_code, $r->digits, $r->xien_size, $r->dai_count);
                $this->map[$k] = ['buy_rate'=>(float)$r->buy_rate, 'payout'=>(float)$r->payout];
            }
        }

        return $this;
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
