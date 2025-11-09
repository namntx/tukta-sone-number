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

    public function countLo3(string $n3): int {
        $n3 = str_pad(preg_replace('/\D/','',$n3), 3, '0', STR_PAD_LEFT);
        // Kiểm tra trong tails3_counts nếu có
        if (isset($this->tails3_counts) && is_array($this->tails3_counts)) {
            return (int)($this->tails3_counts[$n3] ?? 0);
        }
        // Fallback: đếm từ all_numbers
        return $this->countTailsFromNumbers($n3, 3);
    }

    public function countLo4(string $n4): int {
        $n4 = str_pad(preg_replace('/\D/','',$n4), 4, '0', STR_PAD_LEFT);
        // Đếm từ all_numbers vì không có tails4_counts
        return $this->countTailsFromNumbers($n4, 4);
    }

    /**
     * Đếm số lần xuất hiện của n số cuối trong all_numbers
     * 
     * Ví dụ: 
     * - digits = 2, target = "23": đếm số có 2 số cuối là "23" (123, 023, 923, ...)
     * - digits = 3, target = "345": đếm số có 3 số cuối là "345" (12345, 00345, ...)
     * - digits = 4, target = "1234": đếm số có 4 số cuối là "1234" (51234, 001234, ...)
     */
    protected function countTailsFromNumbers(string $target, int $digits): int {
        $target = str_pad(preg_replace('/\D/','',$target), $digits, '0', STR_PAD_LEFT);
        $allNumbers = $this->all_numbers ?? [];
        $count = 0;
        
        foreach ($allNumbers as $number) {
            // Chuyển số thành string và lấy n số cuối
            $numStr = (string)$number;
            // Nếu số có ít hơn digits chữ số, pad với 0 ở đầu
            if (strlen($numStr) < $digits) {
                $numStr = str_pad($numStr, $digits, '0', STR_PAD_LEFT);
            }
            // Lấy n số cuối
            $tail = substr($numStr, -$digits);
            if ($tail === $target) {
                $count++;
            }
        }
        
        return $count;
    }

    public function matchDau(string $n2): bool {
        $n2 = str_pad(preg_replace('/\D/','',$n2), 2, '0', STR_PAD_LEFT);
        // Cược đầu tính 2 số cuối của giải 8
        $g8Last2 = $this->getG8Last2();
        return $g8Last2 === $n2;
    }

    /**
     * Lấy 2 số cuối của giải 8
     * @return string|null
     */
    public function getG8Last2(): ?string {
        $prizes = $this->prizes ?? [];
        $g8 = $prizes['g8'] ?? [];

        if (empty($g8) || !isset($g8[0])) {
            return null;
        }

        $g8Number = (string)$g8[0];
        // Giải 8 thường là 2 số, lấy 2 số cuối (đảm bảo padding nếu cần)
        $g8Number = str_pad(preg_replace('/\D/','',$g8Number), 2, '0', STR_PAD_LEFT);
        return substr($g8Number, -2);
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
