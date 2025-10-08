<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\LotterySchedule;

class LotteryScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $schedules = [
            // Thứ Hai
            [
                'day_of_week' => 'Thứ Hai',
                'region' => 'Nam',
                'main_station' => 'TP.HCM',
                'sub_stations' => ['Đồng Tháp', 'Cà Mau'],
            ],
            [
                'day_of_week' => 'Thứ Hai',
                'region' => 'Trung',
                'main_station' => 'Phú Yên',
                'sub_stations' => ['Thừa Thiên Huế'],
            ],
            [
                'day_of_week' => 'Thứ Hai',
                'region' => 'Bắc',
                'main_station' => 'Hà Nội',
                'sub_stations' => [],
            ],

            // Thứ Ba
            [
                'day_of_week' => 'Thứ Ba',
                'region' => 'Nam',
                'main_station' => 'Vũng Tàu',
                'sub_stations' => ['Bến Tre', 'Bạc Liêu'],
            ],
            [
                'day_of_week' => 'Thứ Ba',
                'region' => 'Trung',
                'main_station' => 'Quảng Nam',
                'sub_stations' => ['Đắk Lắk'],
            ],
            [
                'day_of_week' => 'Thứ Ba',
                'region' => 'Bắc',
                'main_station' => 'Quảng Ninh',
                'sub_stations' => [],
            ],

            // Thứ Tư
            [
                'day_of_week' => 'Thứ Tư',
                'region' => 'Nam',
                'main_station' => 'Đồng Nai',
                'sub_stations' => ['Cần Thơ', 'Sóc Trăng'],
            ],
            [
                'day_of_week' => 'Thứ Tư',
                'region' => 'Trung',
                'main_station' => 'Khánh Hòa',
                'sub_stations' => ['Đà Nẵng'],
            ],
            [
                'day_of_week' => 'Thứ Tư',
                'region' => 'Bắc',
                'main_station' => 'Bắc Ninh',
                'sub_stations' => [],
            ],

            // Thứ Năm
            [
                'day_of_week' => 'Thứ Năm',
                'region' => 'Nam',
                'main_station' => 'Tây Ninh',
                'sub_stations' => ['An Giang', 'Bình Thuận'],
            ],
            [
                'day_of_week' => 'Thứ Năm',
                'region' => 'Trung',
                'main_station' => 'Quảng Bình',
                'sub_stations' => ['Bình Định', 'Quảng Trị'],
            ],
            [
                'day_of_week' => 'Thứ Năm',
                'region' => 'Bắc',
                'main_station' => 'Hà Nội',
                'sub_stations' => [],
            ],

            // Thứ Sáu
            [
                'day_of_week' => 'Thứ Sáu',
                'region' => 'Nam',
                'main_station' => 'Bình Dương',
                'sub_stations' => ['Vĩnh Long', 'Trà Vinh'],
            ],
            [
                'day_of_week' => 'Thứ Sáu',
                'region' => 'Trung',
                'main_station' => 'Gia Lai',
                'sub_stations' => ['Ninh Thuận'],
            ],
            [
                'day_of_week' => 'Thứ Sáu',
                'region' => 'Bắc',
                'main_station' => 'Hải Phòng',
                'sub_stations' => [],
            ],

            // Thứ Bảy
            [
                'day_of_week' => 'Thứ Bảy',
                'region' => 'Nam',
                'main_station' => 'TP.HCM',
                'sub_stations' => ['Long An', 'Bình Phước', 'Hậu Giang'],
            ],
            [
                'day_of_week' => 'Thứ Bảy',
                'region' => 'Trung',
                'main_station' => 'Quảng Ngãi',
                'sub_stations' => ['Đà Nẵng', 'Đắk Nông'],
            ],
            [
                'day_of_week' => 'Thứ Bảy',
                'region' => 'Bắc',
                'main_station' => 'Nam Định',
                'sub_stations' => [],
            ],

            // Chủ Nhật
            [
                'day_of_week' => 'Chủ Nhật',
                'region' => 'Nam',
                'main_station' => 'Tiền Giang',
                'sub_stations' => ['Kiên Giang', 'Đà Lạt'],
            ],
            [
                'day_of_week' => 'Chủ Nhật',
                'region' => 'Trung',
                'main_station' => 'Khánh Hòa',
                'sub_stations' => ['Kon Tum'],
            ],
            [
                'day_of_week' => 'Chủ Nhật',
                'region' => 'Bắc',
                'main_station' => 'Thái Bình',
                'sub_stations' => [],
            ],
        ];

        foreach ($schedules as $schedule) {
            LotterySchedule::create($schedule);
        }
    }
}