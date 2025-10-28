<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BettingRate;

class BettingRateSeeder extends Seeder
{
    public function run(): void
    {
        // Xóa default cũ (không đụng bản của khách)
        BettingRate::whereNull('customer_id')->delete();

        // Helper
        $ins = function($region, $type, $buy, $payout, array $meta = []) {
            BettingRate::create(array_merge([
                'customer_id'=>null,
                'region'=>$region,
                'type_code'=>$type,
                'buy_rate'=>$buy,
                'payout'=>$payout,
                'is_active'=>true,
            ], [
                'digits'=>$meta['digits'] ?? null,
                'xien_size'=>$meta['xien_size'] ?? null,
                'dai_count'=>$meta['dai_count'] ?? null,
            ]));
        };

        // ===== Miền Bắc (theo ảnh 1) =====
        foreach (['bac'] as $r) {
            // Giá đề
            $ins($r,'de_dau',     0.70, 70);
            $ins($r,'de_duoi',    0.70, 70);
            $ins($r,'de_duoi_4',  0.70, 5000);

            // Bao lô
            $ins($r,'bao_lo',     0.70, 70,   ['digits'=>2]);
            $ins($r,'bao_lo',     0.70, 600,  ['digits'=>3]);
            $ins($r,'bao_lo',     0.70, 5000, ['digits'=>4]);

            // Xiên đá
            $ins($r,'da_thang',   0.70, 600);  // 1 đài
            $ins($r,'da_xien',    0.70, 600,  ['dai_count'=>2]); // “đá chéo” (2 đài)
            $ins($r,'xien',       0.56, 10,   ['xien_size'=>2]);
            $ins($r,'xien',       0.56, 40,   ['xien_size'=>3]);
            $ins($r,'xien',       0.56, 100,  ['xien_size'=>4]);

            // Xỉu chủ
            $ins($r,'xiu_chu',    0.70, 600);
        }

        // ===== Miền Trung (ảnh 2 – có Bảy lô) =====
        foreach (['trung','nam'] as $r) {
            // Bao lô
            $ins($r,'bao_lo',     0.70, 70,   ['digits'=>2]);
            $ins($r,'bao_lo',     0.70, 600,  ['digits'=>3]);
            $ins($r,'bao_lo',     0.70, 5000, ['digits'=>4]);

            // Xiên đá
            $ins($r,'da_thang',   0.70, 600);
            $ins($r,'da_xien',    0.70, 600,  ['dai_count'=>2]);
            $ins($r,'xien',       0.56, 10,   ['xien_size'=>2]);
            $ins($r,'xien',       0.56, 10,   ['xien_size'=>3]); // theo ảnh MT là 10/10/10
            $ins($r,'xien',       0.56, 10,   ['xien_size'=>4]);

            // Xỉu chủ
            $ins($r,'xiu_chu',    0.70, 600);

            // Bảy lô (7 giải cuối) – chỉ MT/MN
            $ins($r,'bay_lo',     0.70, 70,   ['digits'=>2]);
            $ins($r,'bay_lo',     0.70, 650,  ['digits'=>3]);
        }
    }
}
