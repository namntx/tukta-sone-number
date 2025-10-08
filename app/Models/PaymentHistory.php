<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentHistory extends Model
{
    use HasFactory;

    protected $table = 'payment_history';

    protected $fillable = [
        'user_id',
        'subscription_id',
        'plan_id',
        'amount',
        'payment_method',
        'status',
        'transaction_id',
        'notes',
        'paid_at'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime'
    ];

    /**
     * Get the user that made the payment
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the subscription for this payment
     */
    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * Get the plan for this payment
     */
    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Scope for completed payments
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for pending payments
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Get formatted amount
     */
    public function getFormattedAmountAttribute()
    {
        return number_format($this->amount, 0, ',', '.') . ' VNĐ';
    }

    /**
     * Get formatted payment method
     */
    public function getFormattedPaymentMethodAttribute()
    {
        $methods = [
            'cash' => 'Tiền mặt',
            'bank_transfer' => 'Chuyển khoản',
            'credit_card' => 'Thẻ tín dụng',
            'other' => 'Khác'
        ];

        return $methods[$this->payment_method] ?? $this->payment_method;
    }

    /**
     * Get formatted status
     */
    public function getFormattedStatusAttribute()
    {
        $statuses = [
            'pending' => 'Chờ xử lý',
            'completed' => 'Hoàn thành',
            'failed' => 'Thất bại',
            'refunded' => 'Hoàn tiền'
        ];

        return $statuses[$this->status] ?? $this->status;
    }
}