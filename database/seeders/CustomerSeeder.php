<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Customer;
use Faker\Factory as Faker;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('vi_VN');

        // Get users to assign customers
        $users = User::where('role', 'user')->get();

        if ($users->isEmpty()) {
            $this->command->warn('No users found. Please run UserSeeder first.');
            return;
        }

        foreach ($users as $user) {
            // Create 5-10 customers per user
            $customerCount = rand(5, 10);

            for ($i = 0; $i < $customerCount; $i++) {
                $customer = Customer::create([
                    'user_id' => $user->id,
                    'name' => $faker->name,
                    'phone' => $this->generateVietnamesePhone($faker),
                    'is_active' => rand(0, 10) > 1, // 90% active
                    'betting_rates' => $this->generateBettingRates(),
                ]);

                $this->command->info("Created customer: {$customer->name} for user: {$user->email}");
            }
        }

        $this->command->info('Customer seeding completed!');
    }

    /**
     * Generate Vietnamese phone number
     */
    private function generateVietnamesePhone($faker): string
    {
        $prefixes = ['090', '091', '093', '094', '096', '097', '098', '099', '032', '033', '034', '035', '036', '037', '038', '039', '070', '076', '077', '078', '079', '081', '082', '083', '084', '085', '086', '087', '088', '089'];
        $prefix = $prefixes[array_rand($prefixes)];

        return $prefix . $faker->numerify('#######');
    }

    /**
     * Generate betting rates JSON for a customer
     */
    private function generateBettingRates(): array
    {
        $regions = ['bac', 'trung', 'nam'];
        $rates = [];

        foreach ($regions as $region) {
            // Buy rate: 0.85 - 1.0 (xác)
            $buyRate = round(rand(850, 1000) / 1000, 2);

            // Bao lô
            $rates["{$region}:bao_lo:d2"] = [
                'buy_rate' => $buyRate,
                'payout' => $region === 'bac' ? 80 : 80, // Lô 2 số
            ];
            $rates["{$region}:bao_lo:d3"] = [
                'buy_rate' => $buyRate - 0.03,
                'payout' => $region === 'bac' ? 500 : 500, // Lô 3 số
            ];
            $rates["{$region}:bao_lo:d4"] = [
                'buy_rate' => $buyRate - 0.05,
                'payout' => $region === 'bac' ? 3000 : 3000, // Lô 4 số
            ];

            // Đầu
            $rates["{$region}:dau"] = [
                'buy_rate' => $buyRate,
                'payout' => $region === 'bac' ? 70 : 70,
            ];

            // Đuôi
            $rates["{$region}:duoi"] = [
                'buy_rate' => $buyRate,
                'payout' => 70,
            ];

            // Đầu đuôi
            $rates["{$region}:dau_duoi"] = [
                'buy_rate' => $buyRate,
                'payout' => 70,
            ];

            // Xỉu chủ
            $rates["{$region}:xiu_chu"] = [
                'buy_rate' => $buyRate,
                'payout' => 35,
            ];
            $rates["{$region}:xiu_chu_dau"] = [
                'buy_rate' => $buyRate,
                'payout' => 35,
            ];
            $rates["{$region}:xiu_chu_duoi"] = [
                'buy_rate' => $buyRate,
                'payout' => 35,
            ];

            // Đá thẳng
            $rates["{$region}:da_thang"] = [
                'buy_rate' => $buyRate,
                'payout' => 80,
            ];

            // Đá xiên (2/3/4 đài)
            $rates["{$region}:da_xien:c2"] = [
                'buy_rate' => $buyRate,
                'payout' => 80,
            ];
            $rates["{$region}:da_xien:c3"] = [
                'buy_rate' => $buyRate - 0.02,
                'payout' => 80,
            ];
            $rates["{$region}:da_xien:c4"] = [
                'buy_rate' => $buyRate - 0.03,
                'payout' => 80,
            ];

            // Xiên (chỉ Miền Bắc)
            if ($region === 'bac') {
                $rates["{$region}:xien:x2"] = [
                    'buy_rate' => $buyRate,
                    'payout' => 15,
                ];
                $rates["{$region}:xien:x3"] = [
                    'buy_rate' => $buyRate - 0.02,
                    'payout' => 550,
                ];
                $rates["{$region}:xien:x4"] = [
                    'buy_rate' => $buyRate - 0.04,
                    'payout' => 3500,
                ];
            }
        }

        return $rates;
    }
}
