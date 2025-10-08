<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LotterySchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'day_of_week',
        'region',
        'main_station',
        'sub_stations',
        'is_active',
    ];

    protected $casts = [
        'sub_stations' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get all stations for a specific day and region
     */
    public function getAllStations()
    {
        $stations = [$this->main_station];
        if ($this->sub_stations) {
            $stations = array_merge($stations, $this->sub_stations);
        }
        return $stations;
    }

    /**
     * Get stations based on count (2d = main + 1 sub, 3d = main + 2 sub)
     */
    public function getStationsByCount($count = 2)
    {
        $stations = [$this->main_station]; // Luôn bắt đầu với đài chính
        
        if ($count == 2) {
            // 2d: Đài chính + 1 đài phụ ngẫu nhiên
            if (!empty($this->sub_stations)) {
                $randomSubStation = $this->sub_stations[array_rand($this->sub_stations)];
                $stations[] = $randomSubStation;
            }
        } elseif ($count == 3) {
            // 3d: Đài chính + 2 đài phụ ngẫu nhiên
            if (!empty($this->sub_stations)) {
                if (count($this->sub_stations) >= 2) {
                    // Lấy ngẫu nhiên 2 đài phụ
                    $shuffledSubs = $this->sub_stations;
                    shuffle($shuffledSubs);
                    $stations = array_merge($stations, array_slice($shuffledSubs, 0, 2));
                } else {
                    // Nếu chỉ có 1 đài phụ thì lấy đài phụ đó
                    $stations = array_merge($stations, $this->sub_stations);
                }
            }
        } else {
            // Default: trả về tất cả
            return $this->getAllStations();
        }
        
        return $stations;
    }

    /**
     * Get random stations for a specific day and region (deprecated - use getStationsByCount)
     */
    public function getRandomStations($count = 2)
    {
        return $this->getStationsByCount($count);
    }

    /**
     * Scope for active schedules
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for specific day
     */
    public function scopeForDay($query, $dayOfWeek)
    {
        return $query->where('day_of_week', $dayOfWeek);
    }

    /**
     * Scope for specific region
     */
    public function scopeForRegion($query, $region)
    {
        return $query->where('region', $region);
    }

    /**
     * Get schedule for today
     */
    public static function getTodaySchedule($region = null)
    {
        $dayOfWeek = now()->locale('vi')->dayName;
        $query = static::active()->forDay($dayOfWeek);
        
        if ($region) {
            $query->forRegion($region);
        }
        
        return $query->get();
    }
}