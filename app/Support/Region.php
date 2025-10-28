<?php

namespace App\Support;

final class Region
{
    /**
     * Chuẩn hoá về key nội bộ: bac|trung|nam
     */
    public static function normalizeKey(?string $v): string
    {
        $v = mb_strtolower(trim((string)$v), 'UTF-8');

        // alias phổ biến
        $aliases = [
            'bac'   => ['bac','mb','mien bac','bắc','mien bắc','b'],
            'trung' => ['trung','mt','mien trung','miền trung','tr','trg','trung bo','trung bộ','t'],
            'nam'   => ['nam','mn','mien nam','miền nam','n','south'],
        ];
        foreach ($aliases as $key => $list) {
            if (in_array($v, $list, true)) return $key;
        }
        // fallback
        return in_array($v, ['bac','trung','nam'], true) ? $v : 'nam';
    }

    public static function label(string $key): string
    {
        return match ($key) {
            'bac'   => 'Miền Bắc',
            'trung' => 'Miền Trung',
            'nam'   => 'Miền Nam',
            default => 'Miền Nam',
        };
    }
}
