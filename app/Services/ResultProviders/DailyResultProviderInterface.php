<?php

namespace App\Services\ResultProviders;

use Carbon\CarbonInterface;

interface DailyResultProviderInterface
{
    /**
     * Trả về mảng payloads (mỗi đài một phần tử) cho {region,date}.
     * Mỗi payload có dạng:
     * [
     *   'station_code' => 'tn',
     *   'station'      => 'tay ninh',
     *   'region'       => 'nam'|'trung'|'bac',
     *   'draw_date'    => 'YYYY-MM-DD',
     *   'prizes'       => [
     *      'g8'=>['..'], 'g7'=>['..'], 'g6'=>['..','..','..'], 'g5'=>['..'],
     *      'g4'=>[ ...7 giá trị... ], 'g3'=>['..','..'], 'g2'=>['..'],
     *      'g1'=>['..'], 'db'=>['......'],
     *   ],
     * ]
     */
    public function fetchDaily(string $region, CarbonInterface $date): array;
}
