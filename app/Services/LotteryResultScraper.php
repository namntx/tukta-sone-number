<?php

namespace App\Services;

use App\Models\LotteryResult;
use App\Services\ResultProviders\DailyResultProviderInterface;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class LotteryResultScraper
{
    /** @var DailyResultProviderInterface */
    protected DailyResultProviderInterface $dailyProvider;

    public function __construct(DailyResultProviderInterface $dailyProvider)
    {
        $this->dailyProvider = $dailyProvider;
    }

    /**
     * Scrape toàn bộ đài trong {region,date} từ provider (AZ24) và lưu DB.
     * Trả về danh sách LotteryResult đã lưu.
     */
    public function scrapeDaily(CarbonInterface $date, string $region = 'nam'): array
    {
        $date   = Carbon::parse($date)->startOfDay();
        $region = strtolower($region);

        $payloads = $this->dailyProvider->fetchDaily($region, $date);
        $saved    = [];

        foreach ($payloads as $p) {
            $normalized = $this->normalizePayload($p);

            $saved[] = DB::transaction(function () use ($normalized) {
                return LotteryResult::updateOrCreate(
                    [
                        'draw_date'    => $normalized['draw_date'],
                        'station_code' => $normalized['station_code'],
                    ],
                    $normalized
                );
            });
        }

        return $saved;
    }

    /**
     * Chuẩn hoá payload từ provider thành dữ liệu lưu DB
     */
    protected function normalizePayload(array $p): array
    {
        $drawDate    = \Carbon\Carbon::parse($p['draw_date'])->toDateString();
        $region      = strtolower($p['region'] ?? 'nam');
        $station     = strtolower($p['station']);
        $stationCode = strtolower($p['station_code']);
        $prizes      = $p['prizes'] ?? [];

        // Flatten all numbers (giữ chuỗi, không mất leading zero)
        $all = [];
        foreach ($prizes as $list) {
            foreach ($list as $num) {
                $num = preg_replace('/\D/','', (string)$num);
                if ($num === '') continue;
                $all[] = $num;
            }
        }

        $dbFull   = $prizes['db'][0] ?? null;
        $dbFirst2 = $dbFull ? substr($dbFull, 0, 2) : null;
        $dbLast2  = $dbFull ? substr($dbFull, -2)   : null;
        $dbFirst3 = $dbFull ? substr($dbFull, 0, 3) : null;
        $dbLast3  = $dbFull ? substr($dbFull, -3)   : null;

        $tails2 = [];
        $tails3 = [];
        $heads2 = [];
        foreach ($all as $raw) {
            $n2 = substr($raw, -2);
            $tails2[$n2] = ($tails2[$n2] ?? 0) + 1;

            if (strlen($raw) >= 3) {
                $n3 = substr($raw, -3);
                $tails3[$n3] = ($tails3[$n3] ?? 0) + 1;
            }

            if (strlen($raw) >= 2) {
                $h2 = substr($raw, 0, 2);
                $heads2[$h2] = ($heads2[$h2] ?? 0) + 1;
            }
        }

        return [
            'draw_date'       => $drawDate,
            'region'          => $region,
            'station'         => $station,
            'station_code'    => $stationCode,
            'prizes'          => $prizes,
            'all_numbers'     => array_values(array_unique($all)),
            'db_full'         => $dbFull,
            'db_first2'       => $dbFirst2,
            'db_last2'        => $dbLast2,
            'db_first3'       => $dbFirst3,
            'db_last3'        => $dbLast3,
            'tails2_counts'   => $tails2,
            'tails3_counts'   => $tails3,
            'heads2_counts'   => $heads2,
        ];
    }
}
