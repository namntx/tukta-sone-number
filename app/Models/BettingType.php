<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BettingType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'syntaxes',
        'description',
        'is_active',
        'sort_order'
    ];

    protected $casts = [
        'syntaxes' => 'array',
        'is_active' => 'boolean'
    ];

    /**
     * Get betting tickets for this type
     */
    public function bettingTickets()
    {
        return $this->hasMany(BettingTicket::class);
    }

    /**
     * Get betting rates for this type
     */
    public function bettingRates()
    {
        return $this->hasMany(BettingRate::class);
    }

    /**
     * Check if a syntax matches this betting type
     */
    public function matchesSyntax($syntax)
    {
        return in_array(strtolower(trim($syntax)), array_map('strtolower', $this->syntaxes));
    }

    /**
     * Scope for active betting types
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope ordered by sort_order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
