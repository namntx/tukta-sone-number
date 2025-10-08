<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plan;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Gói 1 tháng',
                'slug' => '1-month',
                'description' => 'Gói subscription 1 tháng với đầy đủ tính năng',
                'price' => 100000,
                'duration_days' => 30,
                'is_active' => true,
                'is_custom' => false,
                'features' => [
                    'Truy cập đầy đủ tính năng',
                    'Hỗ trợ 24/7',
                    'Báo cáo chi tiết',
                    'Tích hợp API'
                ],
                'sort_order' => 1
            ],
            [
                'name' => 'Gói 3 tháng',
                'slug' => '3-month',
                'description' => 'Gói subscription 3 tháng với ưu đãi đặc biệt',
                'price' => 270000,
                'duration_days' => 90,
                'is_active' => true,
                'is_custom' => false,
                'features' => [
                    'Truy cập đầy đủ tính năng',
                    'Hỗ trợ 24/7',
                    'Báo cáo chi tiết',
                    'Tích hợp API',
                    'Ưu đãi 10% so với gói 1 tháng'
                ],
                'sort_order' => 2
            ],
            [
                'name' => 'Gói 1 năm',
                'slug' => '1-year',
                'description' => 'Gói subscription 1 năm với ưu đãi lớn nhất',
                'price' => 1000000,
                'duration_days' => 365,
                'is_active' => true,
                'is_custom' => false,
                'features' => [
                    'Truy cập đầy đủ tính năng',
                    'Hỗ trợ 24/7',
                    'Báo cáo chi tiết',
                    'Tích hợp API',
                    'Ưu đãi 17% so với gói 1 tháng',
                    'Tính năng premium độc quyền'
                ],
                'sort_order' => 3
            ],
            [
                'name' => 'Gói Custom',
                'slug' => 'custom',
                'description' => 'Gói subscription tùy chỉnh theo nhu cầu',
                'price' => 0,
                'duration_days' => 1,
                'is_active' => true,
                'is_custom' => true,
                'features' => [
                    'Tùy chỉnh theo nhu cầu',
                    'Giá cả linh hoạt',
                    'Thời gian linh hoạt',
                    'Tính năng đặc biệt'
                ],
                'sort_order' => 4
            ]
        ];

        foreach ($plans as $plan) {
            Plan::create($plan);
        }
    }
}