<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'phone',
        'betting_rates',
        'total_win_amount',
        'total_lose_amount',
        'daily_win_amount',
        'daily_lose_amount',
        'monthly_win_amount',
        'monthly_lose_amount',
        'yearly_win_amount',
        'yearly_lose_amount',
        'is_active'
    ];

    protected $casts = [
        'betting_rates' => 'array',
        'total_win_amount' => 'decimal:2',
        'total_lose_amount' => 'decimal:2',
        'daily_win_amount' => 'decimal:2',
        'daily_lose_amount' => 'decimal:2',
        'monthly_win_amount' => 'decimal:2',
        'monthly_lose_amount' => 'decimal:2',
        'yearly_win_amount' => 'decimal:2',
        'yearly_lose_amount' => 'decimal:2',
        'is_active' => 'boolean'
    ];

    /**
     * Get the user that owns the customer
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get betting tickets for this customer
     */
    public function bettingTickets()
    {
        return $this->hasMany(BettingTicket::class);
    }

    /**
     * Get betting rates for this customer (legacy table relationship)
     */
    public function bettingRates()
    {
        return $this->hasMany(BettingRate::class);
    }

    /**
     * Set a betting rate for specific type and region
     *
     * @param string $region  Region: bac, trung, nam
     * @param string $typeCode  Bet type code
     * @param float $buyRate  Buy rate (xác)
     * @param float $payout  Payout multiplier
     * @param int|null $digits  For bao_lo: 2, 3, or 4
     * @param int|null $xienSize  For xien: 2, 3, or 4
     * @param int|null $daiCount  For multi-station bets
     */
    public function setRate(
        string $region,
        string $typeCode,
        float $buyRate,
        float $payout,
        ?int $digits = null,
        ?int $xienSize = null,
        ?int $daiCount = null
    ): void {
        $rates = $this->betting_rates ?? [];

        // Build composite key
        $key = $this->buildRateKey($region, $typeCode, $digits, $xienSize, $daiCount);

        $rates[$key] = [
            'buy_rate' => $buyRate,
            'payout' => $payout,
        ];

        $this->betting_rates = $rates;
        $this->save();
    }

    /**
     * Get a betting rate for specific type and region
     */
    public function getRate(
        string $region,
        string $typeCode,
        ?int $digits = null,
        ?int $xienSize = null,
        ?int $daiCount = null
    ): ?array {
        $rates = $this->betting_rates ?? [];
        $key = $this->buildRateKey($region, $typeCode, $digits, $xienSize, $daiCount);

        return $rates[$key] ?? null;
    }

    /**
     * Build composite key for rate storage
     */
    protected function buildRateKey(
        string $region,
        string $typeCode,
        ?int $digits,
        ?int $xienSize,
        ?int $daiCount
    ): string {
        $parts = [$region, $typeCode];

        if ($digits !== null) $parts[] = "d{$digits}";
        if ($xienSize !== null) $parts[] = "x{$xienSize}";
        if ($daiCount !== null) $parts[] = "c{$daiCount}";

        return implode(':', $parts);
    }

    /**
     * Get net profit (win - lose)
     */
    public function getNetProfitAttribute()
    {
        return $this->total_win_amount - $this->total_lose_amount;
    }

    /**
     * Get daily net profit
     */
    public function getDailyNetProfitAttribute()
    {
        return $this->daily_win_amount - $this->daily_lose_amount;
    }

    /**
     * Get monthly net profit
     */
    public function getMonthlyNetProfitAttribute()
    {
        return $this->monthly_win_amount - $this->monthly_lose_amount;
    }

    /**
     * Get yearly net profit
     */
    public function getYearlyNetProfitAttribute()
    {
        return $this->yearly_win_amount - $this->yearly_lose_amount;
    }

    /**
     * Scope for active customers
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
