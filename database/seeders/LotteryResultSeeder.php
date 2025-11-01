<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LotteryResult;
use Carbon\Carbon;

class LotteryResultSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create results for last 30 days
        $startDate = Carbon::today()->subDays(30);
        $endDate = Carbon::today()->subDay();

        for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
            // Miền Bắc (every day)
            $this->createBacResult($date->format('Y-m-d'));

            // Miền Nam (various stations)
            $dayOfWeek = $date->dayOfWeek;
            $this->createNamResults($date->format('Y-m-d'), $dayOfWeek);

            // Miền Trung (various stations)
            $this->createTrungResults($date->format('Y-m-d'), $dayOfWeek);
        }

        $this->command->info('Lottery results seeded successfully!');
    }

    /**
     * Create Miền Bắc result
     */
    private function createBacResult(string $date): void
    {
        $results = [
            'giai_db' => [$this->randomNumber(5)],
            'giai_1' => [$this->randomNumber(5)],
            'giai_2' => [$this->randomNumber(5), $this->randomNumber(5)],
            'giai_3' => array_fill(0, 6, null),
            'giai_4' => array_fill(0, 4, null),
            'giai_5' => array_fill(0, 6, null),
            'giai_6' => [$this->randomNumber(3), $this->randomNumber(3), $this->randomNumber(3)],
            'giai_7' => [$this->randomNumber(2), $this->randomNumber(2), $this->randomNumber(2), $this->randomNumber(2)],
        ];

        // Fill giai_3, giai_4, giai_5 with random numbers
        for ($i = 0; $i < 6; $i++) {
            $results['giai_3'][$i] = $this->randomNumber(5);
            $results['giai_5'][$i] = $this->randomNumber(4);
        }
        for ($i = 0; $i < 4; $i++) {
            $results['giai_4'][$i] = $this->randomNumber(5);
        }

        LotteryResult::create([
            'draw_date' => $date,
            'station' => 'ha noi',
            'region' => 'bac',
            'results' => $results,
        ]);
    }

    /**
     * Create Miền Nam results based on day of week
     */
    private function createNamResults(string $date, int $dayOfWeek): void
    {
        $stations = [];

        switch ($dayOfWeek) {
            case 0: // Sunday
                $stations = ['tien giang', 'kien giang', 'da lat'];
                break;
            case 1: // Monday
                $stations = ['tp.hcm', 'dong thap', 'ca mau'];
                break;
            case 2: // Tuesday
                $stations = ['ben tre', 'vung tau', 'bac lieu'];
                break;
            case 3: // Wednesday
                $stations = ['dong nai', 'can tho', 'soc trang'];
                break;
            case 4: // Thursday
                $stations = ['an giang', 'tay ninh', 'binh thuan'];
                break;
            case 5: // Friday
                $stations = ['vinh long', 'binh duong', 'tra vinh'];
                break;
            case 6: // Saturday
                $stations = ['tp.hcm', 'long an', 'binh phuoc', 'hau giang'];
                break;
        }

        foreach ($stations as $station) {
            $this->createNamResult($date, $station);
        }
    }

    /**
     * Create a single Nam result
     */
    private function createNamResult(string $date, string $station): void
    {
        $results = [
            'giai_db' => [$this->randomNumber(6)],
            'giai_1' => [$this->randomNumber(5)],
            'giai_2' => [$this->randomNumber(5)],
            'giai_3' => [$this->randomNumber(5), $this->randomNumber(5)],
            'giai_4' => array_fill(0, 7, null),
            'giai_5' => array_fill(0, 7, null),
            'giai_6' => [$this->randomNumber(4), $this->randomNumber(4), $this->randomNumber(4)],
            'giai_7' => [$this->randomNumber(3), $this->randomNumber(3), $this->randomNumber(3), $this->randomNumber(3)],
            'giai_8' => [$this->randomNumber(2)],
        ];

        for ($i = 0; $i < 7; $i++) {
            $results['giai_4'][$i] = $this->randomNumber(5);
            $results['giai_5'][$i] = $this->randomNumber(4);
        }

        LotteryResult::create([
            'draw_date' => $date,
            'station' => $station,
            'region' => 'nam',
            'results' => $results,
        ]);
    }

    /**
     * Create Miền Trung results based on day of week
     */
    private function createTrungResults(string $date, int $dayOfWeek): void
    {
        $stations = [];

        switch ($dayOfWeek) {
            case 0: // Sunday
                $stations = ['khanh hoa'];
                break;
            case 1: // Monday
                $stations = ['phu yen', 'thua thien hue'];
                break;
            case 2: // Tuesday
                $stations = ['da nang', 'quang nam'];
                break;
            case 3: // Wednesday
                $stations = ['da nang', 'khanh hoa'];
                break;
            case 4: // Thursday
                $stations = ['binh dinh', 'quang tri'];
                break;
            case 5: // Friday
                $stations = ['gia lai', 'ninh thuan'];
                break;
            case 6: // Saturday
                $stations = ['da nang', 'quang ngai', 'da nang'];
                break;
        }

        foreach ($stations as $station) {
            $this->createTrungResult($date, $station);
        }
    }

    /**
     * Create a single Trung result
     */
    private function createTrungResult(string $date, string $station): void
    {
        // Similar structure to Nam
        $results = [
            'giai_db' => [$this->randomNumber(6)],
            'giai_1' => [$this->randomNumber(5)],
            'giai_2' => [$this->randomNumber(5)],
            'giai_3' => [$this->randomNumber(5), $this->randomNumber(5)],
            'giai_4' => array_fill(0, 7, null),
            'giai_5' => array_fill(0, 7, null),
            'giai_6' => [$this->randomNumber(4), $this->randomNumber(4), $this->randomNumber(4)],
            'giai_7' => [$this->randomNumber(3), $this->randomNumber(3), $this->randomNumber(3), $this->randomNumber(3)],
            'giai_8' => [$this->randomNumber(2)],
        ];

        for ($i = 0; $i < 7; $i++) {
            $results['giai_4'][$i] = $this->randomNumber(5);
            $results['giai_5'][$i] = $this->randomNumber(4);
        }

        LotteryResult::create([
            'draw_date' => $date,
            'station' => $station,
            'region' => 'trung',
            'results' => $results,
        ]);
    }

    /**
     * Generate random number with N digits
     */
    private function randomNumber(int $digits): string
    {
        $min = $digits === 1 ? 0 : pow(10, $digits - 1);
        $max = pow(10, $digits) - 1;

        return str_pad((string)rand($min, $max), $digits, '0', STR_PAD_LEFT);
    }
}
