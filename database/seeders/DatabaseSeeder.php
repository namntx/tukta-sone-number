<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Run all seeders in order to populate the database with sample data.
     *
     * Usage:
     *   php artisan db:seed
     *   php artisan migrate:fresh --seed
     */
    public function run(): void
    {
        $this->command->info('🌱 Starting database seeding...');
        $this->command->newLine();

        $this->call([
            // 1. System configuration
            PlanSeeder::class,
            BettingTypeSeeder::class,
            StationSeeder::class,
            LotteryScheduleSeeder::class,
            BettingRateSeeder::class, // Global rates

            // 2. Users & Customers
            UserSeeder::class,
            CustomerSeeder::class, // With betting_rates JSON

            // 3. Lottery data
            LotteryResultSeeder::class, // 30 days of results

            // 4. Betting tickets
            BettingTicketSeeder::class, // 14 days of tickets
        ]);

        $this->command->newLine();
        $this->command->info('✅ Database seeding completed successfully!');
        $this->command->newLine();
        $this->displaySummary();
    }

    /**
     * Display seeding summary
     */
    private function displaySummary(): void
    {
        $this->command->table(
            ['Model', 'Count'],
            [
                ['Users', \App\Models\User::count()],
                ['Customers', \App\Models\Customer::count()],
                ['Betting Types', \App\Models\BettingType::count()],
                ['Betting Rates', \App\Models\BettingRate::count()],
                ['Lottery Results', \App\Models\LotteryResult::count()],
                ['Betting Tickets', \App\Models\BettingTicket::count()],
                ['Plans', \App\Models\Plan::count()],
            ]
        );

        $this->command->newLine();
        $this->command->info('📝 Demo Accounts:');
        $this->command->info('  Admin: admin@keki.com / password');
        $this->command->info('  User:  user@keki.com / password');
        $this->command->newLine();
    }
}
