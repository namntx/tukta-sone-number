<?php

namespace App\Services\ResultProviders;

use Carbon\CarbonInterface;

interface ResultProviderInterface
{
    /**
     * Trả về cấu trúc:
     * [
     *   'station_code' => 'tn',
     *   'station'      => 'tay ninh',
     *   'region'       => 'nam',
     *   'draw_date'    => '2025-10-19',
     *   'prizes'       => [
     *      'db' => ['123456'],
     *      'g1' => ['12345'],
     *      'g2' => ['12345','...'],
     *      ...
     *   ],
     * ]
     * NẾU không có (chưa quay/hết dữ liệu) -> return null
     */
    public function fetch(string $stationCode, CarbonInterface $date): ?array;
}
