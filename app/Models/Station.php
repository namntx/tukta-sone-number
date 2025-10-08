<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Station extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'syntaxes',
        'region',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'syntaxes' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Check if a syntax matches this station
     */
    public function matchesSyntax($syntax)
    {
        $syntax = strtolower(trim($syntax));
        $syntaxes = array_map('strtolower', $this->syntaxes);
        
        return in_array($syntax, $syntaxes);
    }

    /**
     * Find station by syntax
     */
    public static function findBySyntax($syntax)
    {
        $syntax = strtolower(trim($syntax));
        
        return static::active()->get()->first(function ($station) use ($syntax) {
            return $station->matchesSyntax($syntax);
        });
    }

    /**
     * Scope for active stations
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for stations by region
     */
    public function scopeByRegion($query, $region)
    {
        return $query->where('region', $region);
    }

    /**
     * Scope for ordered stations
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}