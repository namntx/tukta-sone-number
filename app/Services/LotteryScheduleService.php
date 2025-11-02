<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Service để resolve đài chính và đài phụ theo ngày và miền
 */
class LotteryScheduleService
{
    /**
     * Lấy đài chính và đài phụ theo ngày và miền
     * 
     * @param string|CarbonInterface $date Ngày cần tra (hoặc Carbon instance)
     * @param string $region Miền: 'bac', 'trung', 'nam'
     * @return array{main: string|null, secondary: array<string>}
     */
    public function getStationsByDateAndRegion(string|CarbonInterface $date, string $region): array
    {
        $carbon = $date instanceof CarbonInterface ? $date : Carbon::parse($date);
        $dayOfWeek = $carbon->dayOfWeek; // 0=Sunday, 1=Monday, ..., 6=Saturday
        
        return $this->getStationsByDayOfWeek($dayOfWeek, $region);
    }

    /**
     * Lấy đài chính và đài phụ theo thứ trong tuần và miền
     * 
     * @param int $dayOfWeek 0=Sunday, 1=Monday, ..., 6=Saturday
     * @param string $region 'bac', 'trung', 'nam'
     * @return array{main: string|null, secondary: array<string>}
     */
    public function getStationsByDayOfWeek(int $dayOfWeek, string $region): array
    {
        $schedule = $this->getSchedule();
        
        // Normalize region
        $region = strtolower($region);
        if (!in_array($region, ['bac', 'trung', 'nam'], true)) {
            $region = 'nam'; // default
        }

        $dayData = $schedule[$dayOfWeek] ?? null;
        if (!$dayData) {
            return ['main' => null, 'secondary' => []];
        }

        $regionData = $dayData[$region] ?? null;
        if (!$regionData) {
            return ['main' => null, 'secondary' => []];
        }

        return [
            'main' => $regionData['main'] ?? null,
            'secondary' => $regionData['secondary'] ?? [],
        ];
    }

    /**
     * Lấy N đài (đài chính + đài phụ) theo ngày và miền
     * 
     * @param int $count Số đài cần lấy (2, 3, hoặc 4)
     * @param string|CarbonInterface $date
     * @param string $region
     * @return array<string> Danh sách tên đài canonical
     */
    public function getNStations(int $count, string|CarbonInterface $date, string $region): array
    {
        $stations = $this->getStationsByDateAndRegion($date, $region);
        
        $result = [];
        
        // Thêm đài chính trước
        if ($stations['main']) {
            $result[] = $this->normalizeStationName($stations['main']);
        }
        
        // Thêm đài phụ cho đủ số lượng
        $needed = $count - count($result);
        $secondary = array_slice($stations['secondary'], 0, $needed);
        
        foreach ($secondary as $station) {
            $result[] = $this->normalizeStationName($station);
        }
        
        return $result;
    }

    /**
     * Chuẩn hóa tên đài thành format lowercase không dấu
     * Format này khớp với parser (tay ninh, an giang, tp.hcm, etc.)
     */
    private function normalizeStationName(string $name): string
    {
        // Map từ tên đầy đủ có dấu sang lowercase không dấu
        // Format này khớp với stationAliases trong BettingMessageParser
        $map = [
            // Miền Nam
            'TP.HCM' => 'tp.hcm',
            'Đồng Tháp' => 'dong thap',
            'Cà Mau' => 'ca mau',
            'Vũng Tàu' => 'vung tau',
            'Bến Tre' => 'ben tre',
            'Bạc Liêu' => 'bac lieu',
            'Đồng Nai' => 'dong nai',
            'Cần Thơ' => 'can tho',
            'Sóc Trăng' => 'soc trang',
            'Tây Ninh' => 'tay ninh',
            'An Giang' => 'an giang',
            'Bình Thuận' => 'binh thuan',
            'Bình Dương' => 'binh duong',
            'Vĩnh Long' => 'vinh long',
            'Trà Vinh' => 'tra vinh',
            'Tiền Giang' => 'tien giang',
            'Kiên Giang' => 'kien giang',
            'Đà Lạt' => 'da lat',
            'Lâm Đồng' => 'da lat',
            'Long An' => 'long an',
            'Bình Phước' => 'binh phuoc',
            'Hậu Giang' => 'hau giang',
            
            // Miền Bắc
            'Hà Nội' => 'mien bac',
            'Quảng Ninh' => 'quang ninh',
            'Bắc Ninh' => 'bac ninh',
            'Hải Phòng' => 'hai phong',
            'Nam Định' => 'nam dinh',
            'Thái Bình' => 'thai binh',
            
            // Miền Trung
            'Phú Yên' => 'phu yen',
            'Thừa Thiên Huế' => 'thua thien hue',
            'Quảng Nam' => 'quang nam',
            'Đắk Lắk' => 'dak lak',
            'Khánh Hòa' => 'khanh hoa',
            'Đà Nẵng' => 'da nang',
            'Quảng Bình' => 'quang binh',
            'Bình Định' => 'binh dinh',
            'Quảng Trị' => 'quang tri',
            'Gia Lai' => 'gia lai',
            'Ninh Thuận' => 'ninh thuan',
            'Quảng Ngãi' => 'quang ngai',
            'Đắk Nông' => 'dak nong',
            'Kon Tum' => 'kon tum',
        ];

        return $map[$name] ?? strtolower($name);
    }

    /**
     * Lịch xổ số đầy đủ theo thứ
     * 
     * @return array<int, array<string, array{main: string, secondary: array<string>}>>
     */
    private function getSchedule(): array
    {
        return [
            // 0 = Sunday (Chủ Nhật)
            0 => [
                'nam' => [
                    'main' => 'Tiền Giang',
                    'secondary' => ['Kiên Giang', 'Đà Lạt'],
                ],
                'trung' => [
                    'main' => 'Khánh Hòa',
                    'secondary' => ['Kon Tum'],
                ],
                'bac' => [
                    'main' => 'Thái Bình',
                    'secondary' => [],
                ],
            ],
            
            // 1 = Monday (Thứ Hai)
            1 => [
                'nam' => [
                    'main' => 'TP.HCM',
                    'secondary' => ['Đồng Tháp', 'Cà Mau'],
                ],
                'trung' => [
                    'main' => 'Phú Yên',
                    'secondary' => ['Thừa Thiên Huế'],
                ],
                'bac' => [
                    'main' => 'Hà Nội',
                    'secondary' => [],
                ],
            ],
            
            // 2 = Tuesday (Thứ Ba)
            2 => [
                'nam' => [
                    'main' => 'Vũng Tàu',
                    'secondary' => ['Bến Tre', 'Bạc Liêu'],
                ],
                'trung' => [
                    'main' => 'Quảng Nam',
                    'secondary' => ['Đắk Lắk'],
                ],
                'bac' => [
                    'main' => 'Quảng Ninh',
                    'secondary' => [],
                ],
            ],
            
            // 3 = Wednesday (Thứ Tư)
            3 => [
                'nam' => [
                    'main' => 'Đồng Nai',
                    'secondary' => ['Cần Thơ', 'Sóc Trăng'],
                ],
                'trung' => [
                    'main' => 'Khánh Hòa',
                    'secondary' => ['Đà Nẵng'],
                ],
                'bac' => [
                    'main' => 'Bắc Ninh',
                    'secondary' => [],
                ],
            ],
            
            // 4 = Thursday (Thứ Năm)
            4 => [
                'nam' => [
                    'main' => 'Tây Ninh',
                    'secondary' => ['An Giang', 'Bình Thuận'],
                ],
                'trung' => [
                    'main' => 'Quảng Bình',
                    'secondary' => ['Bình Định', 'Quảng Trị'],
                ],
                'bac' => [
                    'main' => 'Hà Nội',
                    'secondary' => [],
                ],
            ],
            
            // 5 = Friday (Thứ Sáu)
            5 => [
                'nam' => [
                    'main' => 'Bình Dương',
                    'secondary' => ['Vĩnh Long', 'Trà Vinh'],
                ],
                'trung' => [
                    'main' => 'Gia Lai',
                    'secondary' => ['Ninh Thuận'],
                ],
                'bac' => [
                    'main' => 'Hải Phòng',
                    'secondary' => [],
                ],
            ],
            
            // 6 = Saturday (Thứ Bảy)
            6 => [
                'nam' => [
                    'main' => 'TP.HCM',
                    'secondary' => ['Long An', 'Bình Phước', 'Hậu Giang'],
                ],
                'trung' => [
                    'main' => 'Quảng Ngãi',
                    'secondary' => ['Đà Nẵng', 'Đắk Nông'],
                ],
                'bac' => [
                    'main' => 'Nam Định',
                    'secondary' => [],
                ],
            ],
        ];
    }
}

