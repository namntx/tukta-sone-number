<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BettingTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'customer_id',
        'betting_type_id',
        'betting_date',
        'region',
        'station',
        'original_message',
        'parsed_message',
        'betting_data',
        'result',
        'bet_amount',
        'win_amount',
        'payout_amount',
        'status'
    ];

    protected $casts = [
        'betting_date' => 'date',
        'betting_data' => 'array',
        'bet_amount' => 'decimal:2',
        'win_amount' => 'decimal:2',
        'payout_amount' => 'decimal:2'
    ];

    /**
     * Get the user that owns the ticket
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the customer for this ticket
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the betting type for this ticket
     */
    public function bettingType()
    {
        return $this->belongsTo(BettingType::class);
    }

    /**
     * Get formatted bet amount
     */
    public function getFormattedBetAmountAttribute()
    {
        return number_format($this->bet_amount, 0, ',', '.') . ' VNĐ';
    }

    /**
     * Get formatted win amount
     */
    public function getFormattedWinAmountAttribute()
    {
        return number_format($this->win_amount, 0, ',', '.') . ' VNĐ';
    }

    /**
     * Get formatted payout amount
     */
    public function getFormattedPayoutAmountAttribute()
    {
        return number_format($this->payout_amount, 0, ',', '.') . ' VNĐ';
    }

    /**
     * Get result badge class
     */
    public function getResultBadgeClassAttribute()
    {
        return match($this->result) {
            'win' => 'bg-green-100 text-green-800',
            'lose' => 'bg-red-100 text-red-800',
            'pending' => 'bg-yellow-100 text-yellow-800',
            default => 'bg-gray-100 text-gray-800'
        };
    }

    /**
     * Get status badge class
     */
    public function getStatusBadgeClassAttribute()
    {
        return match($this->status) {
            'active' => 'bg-blue-100 text-blue-800',
            'completed' => 'bg-green-100 text-green-800',
            'cancelled' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800'
        };
    }

    /**
     * Scope for today's tickets
     */
    public function scopeToday($query)
    {
        return $query->whereDate('betting_date', today());
    }

    /**
     * Scope for specific date
     */
    public function scopeForDate($query, $date)
    {
        return $query->whereDate('betting_date', $date);
    }

    /**
     * Scope for specific region
     */
    public function scopeForRegion($query, $region)
    {
        return $query->where('region', $region);
    }

    /**
     * Scope for specific customer
     */
    public function scopeForCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }
}
