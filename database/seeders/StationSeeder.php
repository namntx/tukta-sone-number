<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Station;

class StationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $stations = [
            // Đài chánh
            [
                'name' => 'Đài chánh',
                'code' => 'dc',
                'syntaxes' => ['dc', 'chanh', 'ch', 'daichanh', 'dchanh'],
                'region' => 'Bắc',
                'sort_order' => 1,
            ],
            
            // Đài phụ
            [
                'name' => 'Đài phụ',
                'code' => 'dp',
                'syntaxes' => ['dp', 'phu', 'ph', 'dphu', 'daiphu'],
                'region' => 'Bắc',
                'sort_order' => 2,
            ],
            
            // Hà nội, miền bắc
            [
                'name' => 'Hà Nội',
                'code' => 'hn',
                'syntaxes' => ['mb', 'hn', 'hanoi', 'ha noi'],
                'region' => 'Bắc',
                'sort_order' => 3,
            ],
            
            // An giang
            [
                'name' => 'An Giang',
                'code' => 'ag',
                'syntaxes' => ['ag', 'angiang', 'an giang'],
                'region' => 'Nam',
                'sort_order' => 4,
            ],
            
            // Bạc liêu
            [
                'name' => 'Bạc Liêu',
                'code' => 'bl',
                'syntaxes' => ['bl', 'blieu', 'baclieu', 'bac lieu'],
                'region' => 'Nam',
                'sort_order' => 5,
            ],
            
            // Bến tre
            [
                'name' => 'Bến Tre',
                'code' => 'bt',
                'syntaxes' => ['bt', 'btre', 'bentre', 'ben tre'],
                'region' => 'Nam',
                'sort_order' => 6,
            ],
            
            // Bình dương
            [
                'name' => 'Bình Dương',
                'code' => 'bd',
                'syntaxes' => ['db', 'bduong', 'sb', 'binhduong', 'binh duong'],
                'region' => 'Nam',
                'sort_order' => 7,
            ],
            
            // Bình phước
            [
                'name' => 'Bình Phước',
                'code' => 'bp',
                'syntaxes' => ['bp', 'bphuoc', 'binhphuoc', 'binh phuoc'],
                'region' => 'Nam',
                'sort_order' => 8,
            ],
            
            // Bình thuận
            [
                'name' => 'Bình Thuận',
                'code' => 'bth',
                'syntaxes' => ['bth', 'bthuan', 'binhthuan', 'binh thuan'],
                'region' => 'Nam',
                'sort_order' => 9,
            ],
            
            // Cà mau
            [
                'name' => 'Cà Mau',
                'code' => 'cm',
                'syntaxes' => ['cm', 'camau', 'ca mau'],
                'region' => 'Nam',
                'sort_order' => 10,
            ],
            
            // Cần thơ
            [
                'name' => 'Cần Thơ',
                'code' => 'ct',
                'syntaxes' => ['ct', 'ctho', 'cantho', 'can tho'],
                'region' => 'Nam',
                'sort_order' => 11,
            ],
            
            // Đà lạt
            [
                'name' => 'Đà Lạt',
                'code' => 'dl',
                'syntaxes' => ['dl', 'dlat', 'dalat', 'da lat'],
                'region' => 'Nam',
                'sort_order' => 12,
            ],
            
            // Đồng nai
            [
                'name' => 'Đồng Nai',
                'code' => 'dn',
                'syntaxes' => ['dn', 'dnai', 'dongnai', 'dong nai'],
                'region' => 'Nam',
                'sort_order' => 13,
            ],
            
            // Đồng tháp
            [
                'name' => 'Đồng Tháp',
                'code' => 'dt',
                'syntaxes' => ['dt', 'dthap', 'dongthap', 'dong thap'],
                'region' => 'Nam',
                'sort_order' => 14,
            ],
            
            // Hậu giang
            [
                'name' => 'Hậu Giang',
                'code' => 'hg',
                'syntaxes' => ['hg', 'hgiang', 'haugiang'],
                'region' => 'Nam',
                'sort_order' => 15,
            ],
            
            // Kiên giang
            [
                'name' => 'Kiên Giang',
                'code' => 'kg',
                'syntaxes' => ['kg', 'kgiang', 'kiengiang', 'kien giang'],
                'region' => 'Nam',
                'sort_order' => 16,
            ],
            
            // Long an
            [
                'name' => 'Long An',
                'code' => 'la',
                'syntaxes' => ['la', 'lan', 'longan', 'long an'],
                'region' => 'Nam',
                'sort_order' => 17,
            ],
            
            // Sóc trăng
            [
                'name' => 'Sóc Trăng',
                'code' => 'st',
                'syntaxes' => ['st', 'strang', 'soctrang', 'soc trang'],
                'region' => 'Nam',
                'sort_order' => 18,
            ],
            
            // Tây ninh
            [
                'name' => 'Tây Ninh',
                'code' => 'tn',
                'syntaxes' => ['tn', 'tninh', 'tayninh', 'tay ninh'],
                'region' => 'Nam',
                'sort_order' => 19,
            ],
            
            // Tiền giang
            [
                'name' => 'Tiền Giang',
                'code' => 'tg',
                'syntaxes' => ['tg', 'tgiang', 'tien giang', 'tiengiang'],
                'region' => 'Nam',
                'sort_order' => 20,
            ],
            
            // TP.HCM
            [
                'name' => 'TP.HCM',
                'code' => 'tp',
                'syntaxes' => ['tp', 'hcm'],
                'region' => 'Nam',
                'sort_order' => 21,
            ],
            
            // Trà vinh
            [
                'name' => 'Trà Vinh',
                'code' => 'tv',
                'syntaxes' => ['tv', 'tvinh', 'travinh', 'tra vinh'],
                'region' => 'Nam',
                'sort_order' => 22,
            ],
            
            // Vĩnh long
            [
                'name' => 'Vĩnh Long',
                'code' => 'vl',
                'syntaxes' => ['vl', 'vlong', 'vinhlong', 'vinh long'],
                'region' => 'Nam',
                'sort_order' => 23,
            ],
            
            // Vũng tàu
            [
                'name' => 'Vũng Tàu',
                'code' => 'vt',
                'syntaxes' => ['vt', 'vtau', 'vungtau', 'vung tau'],
                'region' => 'Nam',
                'sort_order' => 24,
            ],
        ];

        foreach ($stations as $station) {
            Station::create($station);
        }
    }
}