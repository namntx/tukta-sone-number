<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Customer;
use App\Models\BettingTicket;
use Carbon\Carbon;

class BettingTicketSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::where('role', 'user')->with('customers')->get();

        if ($users->isEmpty()) {
            $this->command->warn('No users found. Please run UserSeeder and CustomerSeeder first.');
            return;
        }

        $sampleMessages = [
            'tn 12 34 56 78 lo10n',
            'hcm 01 23 45 d20n',
            'ag 11 22 33 44 55 dd15n',
            'tphcm 99 88 77 xc dau 10 duoi 15',
            'mb 10 20 30 xi2 5n',
            'dn 05 15 25 35 45 d10n d15n',
            'bt 00 11 22 33 lo5n d10n',
            'dl 77 88 99 dau 20 duoi 10',
        ];

        $regions = ['bac', 'trung', 'nam'];
        $stations = [
            'bac' => ['ha noi'],
            'trung' => ['da nang', 'khanh hoa', 'quang nam'],
            'nam' => ['tp.hcm', 'tay ninh', 'an giang', 'ben tre', 'dong nai'],
        ];

        // Create tickets for last 14 days
        $startDate = Carbon::today()->subDays(14);
        $endDate = Carbon::today();

        foreach ($users as $user) {
            $customers = $user->customers;

            if ($customers->isEmpty()) {
                continue;
            }

            for ($date = clone $startDate; $date->lte($endDate); $date->addDay()) {
                // Create 2-5 tickets per day per user
                $ticketCount = rand(2, 5);

                for ($i = 0; $i < $ticketCount; $i++) {
                    $customer = $customers->random();
                    $region = $regions[array_rand($regions)];
                    $station = $stations[$region][array_rand($stations[$region])];
                    $message = $sampleMessages[array_rand($sampleMessages)];

                    $ticket = BettingTicket::create([
                        'user_id' => $user->id,
                        'customer_id' => $customer->id,
                        'betting_date' => $date->format('Y-m-d'),
                        'region' => $region,
                        'station' => $station,
                        'original_message' => $message,
                        'bet_amount' => rand(50, 500) * 1000, // 50k - 500k
                        'win_amount' => 0,
                        'result' => 'pending',
                        'status' => 'confirmed',
                        'notes' => 'Demo betting ticket',
                    ]);

                    $this->command->info("Created ticket #{$ticket->id} for {$customer->name} on {$date->format('Y-m-d')}");
                }
            }
        }

        $this->command->info('Betting tickets seeded successfully!');
    }
}
