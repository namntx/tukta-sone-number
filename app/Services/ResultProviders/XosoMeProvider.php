<?php

namespace App\Services\ResultProviders;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Http;

class XosoMeProvider implements ResultProviderInterface
{
    public function fetch(string $stationCode, CarbonInterface $date): ?array
    {
        // Ví dụ URL — bạn thay cho đúng nguồn/selector thực tế
        $url = 'https://example.com/xs/'.$stationCode.'?date='.$date->format('d-m-Y');
        $html = Http::timeout(20)->get($url)->body();

        if (!$html) return null;

        // Parse thô bằng regex đơn giản (nên dùng DomCrawler nếu có sẵn)
        // Ở đây minh hoạ: bạn thay bằng CSS selector của site bạn chọn
        $prizes = [
            'db' => $this->matchAll('/class="giaidb".*?>(\d{5,6})</', $html),
            'g1' => $this->matchAll('/class="giai1".*?>(\d{4,5})</', $html),
            'g2' => $this->matchAll('/class="giai2".*?>(\d{4,5})</', $html),
            'g3' => $this->matchAll('/class="giai3".*?>(\d{4,5})</', $html),
            'g4' => $this->matchAll('/class="giai4".*?>(\d{4,5})</', $html),
            'g5' => $this->matchAll('/class="giai5".*?>(\d{4,5})</', $html),
            'g6' => $this->matchAll('/class="giai6".*?>(\d{3,4})</', $html),
            'g7' => $this->matchAll('/class="giai7".*?>(\d{2,3})</', $html),
            'g8' => $this->matchAll('/class="giai8".*?>(\d{2})</', $html),
        ];

        // Nếu thiếu GĐB coi như fail
        if (empty($prizes['db'])) return null;

        return [
            'station_code' => $stationCode,
            'station'      => $this->canonStation($stationCode),
            'region'       => $this->guessRegion($stationCode),
            'draw_date'    => $date->toDateString(),
            'prizes'       => $prizes,
        ];
    }

    private function matchAll(string $pattern, string $html): array {
        preg_match_all($pattern, $html, $m);
        return array_values(array_map(fn($v)=>trim($v), $m[1] ?? []));
    }

    private function canonStation(string $code): string {
        // map ngắn → tên đầy đủ; nên đồng bộ với Parser alias map
        return match (strtolower($code)) {
            'tn' => 'tay ninh',
            'ag' => 'an giang',
            'tg' => 'tien giang',
            default => $code,
        };
    }

    private function guessRegion(string $code): string {
        // đơn giản: tất cả code trên là 'nam'
        return 'nam';
    }
}
