<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LotteryResult extends Model
{
    protected $fillable = [
        'draw_date','region','station','station_code',
        'prizes','all_numbers',
        'db_full','db_first2','db_last2','db_first3','db_last3',
        'tails2_counts','tails3_counts','heads2_counts',
    ];

    protected $casts = [
        'draw_date'      => 'date',
        'prizes'         => 'array',
        'all_numbers'    => 'array',
        'tails2_counts'  => 'array',
        'tails3_counts'  => 'array',
        'heads2_counts'  => 'array',
    ];

    // —— Helpers cho bộ quyết toán ——
    public function countLo2(string $n2): int {
        $n2 = str_pad(preg_replace('/\D/','',$n2), 2, '0', STR_PAD_LEFT);
        return (int)($this->tails2_counts[$n2] ?? 0);
    }

    public function matchDau(string $n2): bool {
        $n2 = str_pad(preg_replace('/\D/','',$n2), 2, '0', STR_PAD_LEFT);
        return $this->db_first2 === $n2;
    }

    public function matchDuoi(string $n2): bool {
        $n2 = str_pad(preg_replace('/\D/','',$n2), 2, '0', STR_PAD_LEFT);
        return $this->db_last2 === $n2;
    }

    public function matchXiuChuLast3(string $n3, bool $dao = false): bool {
        $n3 = str_pad(preg_replace('/\D/','',$n3), 3, '0', STR_PAD_LEFT);
        if (!$dao) return $this->db_last3 === $n3;
        // đảo: so mọi hoán vị 3 số
        $arr = str_split($this->db_last3 ?? '');
        if (count($arr) !== 3) return false;
        sort($arr);
        $target = str_split($n3);
        sort($target);
        return $arr === $target;
    }
}
