<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get subscriptions for this user
     */
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Get payment history for this user
     */
    public function paymentHistory()
    {
        return $this->hasMany(PaymentHistory::class);
    }

    /**
     * Get customers for this user
     */
    public function customers()
    {
        return $this->hasMany(Customer::class);
    }

    /**
     * Get betting tickets for this user
     */
    public function bettingTickets()
    {
        return $this->hasMany(BettingTicket::class);
    }

    /**
     * Get betting rates for this user
     */
    public function bettingRates()
    {
        return $this->hasMany(BettingRate::class);
    }

    /**
     * Get active subscription
     */
    public function activeSubscription()
    {
        return $this->hasOne(Subscription::class)->where('status', 'active')
                    ->where('expires_at', '>', now())
                    ->latest();
    }

    /**
     * Get latest subscription
     */
    public function latestSubscription()
    {
        return $this->hasOne(Subscription::class)->latest();
    }

    /**
     * Check if user is admin
     */
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user has active subscription
     */
    public function hasActiveSubscription()
    {
        return $this->activeSubscription()->exists();
    }

    /**
     * Get subscription status
     */
    public function getSubscriptionStatus()
    {
        $subscription = $this->activeSubscription;
        
        if (!$subscription) {
            return 'none';
        }

        if ($subscription->isExpired()) {
            return 'expired';
        }

        return 'active';
    }

    /**
     * Get days remaining in subscription
     */
    public function getSubscriptionDaysRemaining()
    {
        $subscription = $this->activeSubscription;
        
        if (!$subscription || $subscription->isExpired()) {
            return 0;
        }

        return $subscription->days_remaining;
    }
}
