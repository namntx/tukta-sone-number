<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\PaymentHistory;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Tạo admin user
        $admin = User::create([
            'name' => 'Admin Keki',
            'email' => 'admin@keki.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_active' => true,
            'last_login_at' => now()
        ]);

        // Tạo user thường
        $user = User::create([
            'name' => 'User Demo',
            'email' => 'user@keki.com',
            'password' => Hash::make('password'),
            'role' => 'user',
            'is_active' => true,
            'last_login_at' => now()
        ]);

        // Tạo subscription cho user
        $plan = Plan::where('slug', '1-month')->first();
        
        if ($plan) {
            $subscription = Subscription::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'status' => 'active',
                'starts_at' => now(),
                'expires_at' => now()->addDays($plan->duration_days),
                'amount_paid' => $plan->price,
                'notes' => 'Demo subscription'
            ]);

            // Tạo payment history
            PaymentHistory::create([
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'plan_id' => $plan->id,
                'amount' => $plan->price,
                'payment_method' => 'cash',
                'status' => 'completed',
                'notes' => 'Demo payment',
                'paid_at' => now()
            ]);
        }

        // Tạo thêm một số users khác
        $users = [
            [
                'name' => 'Nguyễn Văn A',
                'email' => 'user1@keki.com',
                'password' => Hash::make('password'),
                'role' => 'user',
                'is_active' => true
            ],
            [
                'name' => 'Trần Thị B',
                'email' => 'user2@keki.com',
                'password' => Hash::make('password'),
                'role' => 'user',
                'is_active' => true
            ],
            [
                'name' => 'Lê Văn C',
                'email' => 'user3@keki.com',
                'password' => Hash::make('password'),
                'role' => 'user',
                'is_active' => false
            ]
        ];

        foreach ($users as $userData) {
            User::create($userData);
        }
    }
}