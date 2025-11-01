<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add JSON column to customers table
        Schema::table('customers', function (Blueprint $table) {
            $table->json('betting_rates')->nullable()->after('phone');
        });

        // 2. Migrate existing data from betting_rates table to customers.betting_rates
        $this->migrateRatesToJson();

        // 3. Optionally drop old betting_rates table (comment out if you want to keep backup)
        // Schema::dropIfExists('betting_rates');
    }

    public function down(): void
    {
        // Restore from JSON back to betting_rates table
        $this->restoreRatesFromJson();

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('betting_rates');
        });
    }

    private function migrateRatesToJson(): void
    {
        // Get all customers
        $customers = DB::table('customers')->get();

        foreach ($customers as $customer) {
            // Get all rates for this customer
            $rates = DB::table('betting_rates')
                ->where('customer_id', $customer->id)
                ->get();

            // Build JSON structure
            $ratesJson = [];
            foreach ($rates as $rate) {
                $key = $this->buildRateKey($rate);
                $ratesJson[$key] = [
                    'buy_rate' => (float) $rate->buy_rate,
                    'payout' => (float) $rate->payout,
                ];
            }

            // Update customer with JSON rates
            DB::table('customers')
                ->where('id', $customer->id)
                ->update(['betting_rates' => json_encode($ratesJson)]);
        }
    }

    private function restoreRatesFromJson(): void
    {
        $customers = DB::table('customers')->whereNotNull('betting_rates')->get();

        foreach ($customers as $customer) {
            $ratesJson = json_decode($customer->betting_rates, true);
            if (!$ratesJson) continue;

            foreach ($ratesJson as $key => $data) {
                $parsed = $this->parseRateKey($key);

                DB::table('betting_rates')->insert([
                    'customer_id' => $customer->id,
                    'region' => $parsed['region'],
                    'type_code' => $parsed['type_code'],
                    'digits' => $parsed['digits'],
                    'xien_size' => $parsed['xien_size'],
                    'dai_count' => $parsed['dai_count'],
                    'buy_rate' => $data['buy_rate'],
                    'payout' => $data['payout'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    private function buildRateKey($rate): string
    {
        // Build composite key: "region:type_code:digits:xien_size:dai_count"
        $parts = [
            $rate->region ?? 'nam',
            $rate->type_code,
        ];

        if ($rate->digits) $parts[] = "d{$rate->digits}";
        if ($rate->xien_size) $parts[] = "x{$rate->xien_size}";
        if ($rate->dai_count) $parts[] = "c{$rate->dai_count}";

        return implode(':', $parts);
    }

    private function parseRateKey(string $key): array
    {
        $parts = explode(':', $key);
        return [
            'region' => $parts[0] ?? 'nam',
            'type_code' => $parts[1] ?? null,
            'digits' => isset($parts[2]) && str_starts_with($parts[2], 'd') ? (int)substr($parts[2], 1) : null,
            'xien_size' => isset($parts[3]) && str_starts_with($parts[3], 'x') ? (int)substr($parts[3], 1) : null,
            'dai_count' => isset($parts[4]) && str_starts_with($parts[4], 'c') ? (int)substr($parts[4], 1) : null,
        ];
    }
};
