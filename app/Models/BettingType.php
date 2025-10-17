<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;
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
        'is_active' => 'boolean',
    ];

    /**
     * alias -> canonical code
     *
     * @return array<string,string>
     */
    public static function aliasMap(): array
    {
        $map = [];
        $all = static::query()->where('is_active', true)->get(['code', 'syntaxes']);
        foreach ($all as $type) {
            $code = (string)$type->code;
            $aliases = is_array($type->syntaxes) ? $type->syntaxes : [];

            $normCode = self::normalizeToken($code);
            if ($normCode !== '') $map[$normCode] = $code;

            foreach ($aliases as $alias) {
                $norm = self::normalizeToken((string)$alias);
                if ($norm === '') continue;
                $map[$norm] = $code;
            }
        }
        return $map;
    }

    public static function normalizeToken(string $token): string
    {
        $t = Str::lower($token);
        $t = Str::ascii($t);
        $t = str_replace(['đ', 'Đ'], ['d', 'd'], $t);
        $t = str_replace([',', '.'], '', $t);
        $t = preg_replace('/[^a-z0-9_]/', '', $t) ?? '';
        return (string)$t;
    }

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
