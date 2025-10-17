<?php
declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class LotterySchedule extends Model
{
    use HasFactory;

    protected $table = 'lottery_schedules';

    protected $fillable = [
        'day_of_week',   // 'Thứ Hai' ... 'Chủ Nhật'
        'region',        // 'Bắc' | 'Trung' | 'Nam'
        'main_station',  // string
        'sub_stations',  // json array
        'is_active',
    ];

    protected $casts = [
        'sub_stations' => 'array',
        'is_active'    => 'boolean',
    ];

    /**
     * Lấy 1 dòng lịch tương ứng ngày + miền.
     */
    public static function forDateRegion(CarbonInterface|string $date, string $region): ?self
    {
        $dt   = $date instanceof CarbonInterface ? $date : CarbonImmutable::parse((string)$date);
        $dow  = self::viDayOfWeek($dt);
        $regV = self::titleRegion($region);

        return static::query()
            ->where('is_active', true)
            ->where('day_of_week', $dow)
            ->where('region', $regV)
            ->first();
    }

    /**
     * Helper trả danh sách đài (main + subs) theo giới hạn $limit (2d/3d).
     *
     * @return array<int,string>  // giữ nguyên giá trị hiển thị trong DB
     */
    public function stationsList(?int $limit = null): array
    {
        $list = array_merge([$this->main_station], is_array($this->sub_stations) ? $this->sub_stations : []);
        $list = array_values(array_filter($list, fn($x) => is_string($x) && trim($x) !== ''));
        if ($limit !== null && $limit > 0) $list = array_slice($list, 0, $limit);
        return $list;
    }

    /**
     * Chuẩn hoá label thứ (ISO 1..7 -> 'Thứ Hai'..'Chủ Nhật')
     */
    public static function viDayOfWeek(CarbonInterface $date): string
    {
        $map = [
            1 => 'Thứ Hai',
            2 => 'Thứ Ba',
            3 => 'Thứ Tư',
            4 => 'Thứ Năm',
            5 => 'Thứ Sáu',
            6 => 'Thứ Bảy',
            7 => 'Chủ Nhật',
        ];
        return $map[(int)$date->isoWeekday()];
    }

    /**
     * 'nam' -> 'Nam', 'trung' -> 'Trung', 'bac' -> 'Bắc'
     */
    public static function titleRegion(string $region): string
    {
        $r = Str::lower($region);
        return match ($r) {
            'bac'  => 'Bắc',
            'trung'=> 'Trung',
            default=> 'Nam',
        };
    }
}
