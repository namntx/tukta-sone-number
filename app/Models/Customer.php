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
     * Get betting rates for this customer
     */
    public function bettingRates()
    {
        return $this->hasMany(BettingRate::class);
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
