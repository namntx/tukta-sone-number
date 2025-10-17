<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LotterySchedule;

class LotteryScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            // Thứ Hai
            ['Thứ Hai','Nam',  'TP.HCM',     ['Đồng Tháp','Cà Mau']],
            ['Thứ Hai','Trung','Phú Yên',    ['Thừa Thiên Huế']],
            ['Thứ Hai','Bắc',  'Hà Nội',     []],

            // Thứ Ba
            ['Thứ Ba','Nam',  'Vũng Tàu',  ['Bến Tre','Bạc Liêu']],
            ['Thứ Ba','Trung','Quảng Nam', ['Đắk Lắk']],
            ['Thứ Ba','Bắc',  'Quảng Ninh',[]],

            // Thứ Tư
            ['Thứ Tư','Nam',  'Đồng Nai',  ['Cần Thơ','Sóc Trăng']],
            ['Thứ Tư','Trung','Khánh Hòa', ['Đà Nẵng']],
            ['Thứ Tư','Bắc',  'Bắc Ninh',  []],

            // Thứ Năm
            ['Thứ Năm','Nam',  'Tây Ninh',   ['An Giang','Bình Thuận']],
            ['Thứ Năm','Trung','Quảng Bình', ['Bình Định','Quảng Trị']],
            ['Thứ Năm','Bắc',  'Hà Nội',     []],

            // Thứ Sáu
            ['Thứ Sáu','Nam',  'Bình Dương', ['Vĩnh Long','Trà Vinh']],
            ['Thứ Sáu','Trung','Gia Lai',     ['Ninh Thuận']],
            ['Thứ Sáu','Bắc',  'Hải Phòng',   []],

            // Thứ Bảy
            ['Thứ Bảy','Nam',  'TP.HCM',      ['Long An','Bình Phước','Hậu Giang']],
            ['Thứ Bảy','Trung','Quảng Ngãi',  ['Đà Nẵng','Đắk Nông']],
            ['Thứ Bảy','Bắc',  'Nam Định',    []],

            // Chủ Nhật
            ['Chủ Nhật','Nam',  'Tiền Giang', ['Kiên Giang','Đà Lạt']],
            ['Chủ Nhật','Trung','Khánh Hòa',  ['Kon Tum']],
            ['Chủ Nhật','Bắc',  'Thái Bình',  []],
        ];

        foreach ($rows as [$dow,$region,$main,$subs]) {
            LotterySchedule::updateOrCreate(
                ['day_of_week' => $dow, 'region' => $region],
                ['main_station' => $main, 'sub_stations' => $subs, 'is_active' => true]
            );
        }
    }
}
