<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BettingRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'customer_id',
        'betting_type_id',
        'win_rate',
        'lose_rate',
        'is_active'
    ];

    protected $casts = [
        'win_rate' => 'decimal:4',
        'lose_rate' => 'decimal:4',
        'is_active' => 'boolean'
    ];

    /**
     * Get the user that owns the rate
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the customer for this rate
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the betting type for this rate
     */
    public function bettingType()
    {
        return $this->belongsTo(BettingType::class);
    }

    /**
     * Get formatted win rate percentage
     */
    public function getFormattedWinRateAttribute()
    {
        return number_format($this->win_rate * 100, 2) . '%';
    }

    /**
     * Get formatted lose rate percentage
     */
    public function getFormattedLoseRateAttribute()
    {
        return number_format($this->lose_rate * 100, 2) . '%';
    }

    /**
     * Scope for active rates
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for specific customer
     */
    public function scopeForCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    /**
     * Scope for specific betting type
     */
    public function scopeForBettingType($query, $bettingTypeId)
    {
        return $query->where('betting_type_id', $bettingTypeId);
    }
}
