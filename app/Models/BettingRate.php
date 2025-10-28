<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BettingRate extends Model
{
    protected $fillable = [
        'customer_id',
        'region',
        'type_code',
        'digits',
        'xien_size',
        'dai_count',
        'buy_rate',
        'payout',
        'is_active',
    ];

    protected $casts = [
        'buy_rate'  => 'decimal:2',
        'payout'    => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // ===== Relations =====
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    // ===== Scopes =====
    public function scopeActive($q)      { return $q->where('is_active', true); }
    public function scopeRegion($q,$r)   { return $q->where('region', $r); }
    public function scopeType($q,$code)  { return $q->where('type_code',$code); }

    // Match phụ: truyền mảng meta ['digits'=>2,'xien_size'=>null,'dai_count'=>2]...
    public function scopeMatchMeta($q, array $meta)
    {
        foreach (['digits','xien_size','dai_count'] as $k) {
            $q->where(function($qq) use ($k,$meta){
                // cho phép null (wildcard) để fallback
                if (array_key_exists($k,$meta) && $meta[$k] !== null) {
                    $qq->where($k, $meta[$k])->orWhereNull($k);
                } else {
                    $qq->whereNull($k);
                }
            });
        }
        return $q;
    }
}
