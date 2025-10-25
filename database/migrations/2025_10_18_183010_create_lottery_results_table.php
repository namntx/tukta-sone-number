<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('lottery_results', function (Blueprint $table) {
            $table->id();
            $table->date('draw_date')->index();
            $table->string('region', 10)->index();        // nam | trung | bac
            $table->string('station');                    // ví dụ: 'tay ninh'
            $table->string('station_code')->index();      // ví dụ: 'tn' (alias chính)
            $table->json('prizes');                       // { "db": ["xxxxxx"], "g1": ["xxxxx"], ... }
            $table->json('all_numbers');                  // mảng phẳng tất cả số (chuỗi, giữ 0 ở đầu)
            // Chỉ số phục vụ tính nhanh
            $table->string('db_full')->nullable();        // GĐB full chuỗi, nếu có
            $table->string('db_first2', 2)->nullable();   // 2 số đầu GĐB
            $table->string('db_last2', 2)->nullable();    // 2 số cuối GĐB
            $table->string('db_first3', 3)->nullable();   // 3 số đầu GĐB
            $table->string('db_last3', 3)->nullable();    // 3 số cuối GĐB
            $table->json('tails2_counts');                // { "00": n, ..., "99": n }
            $table->json('tails3_counts');                // { "000": n, ..., "999": n } (chỉ lưu keys có nháy)
            $table->json('heads2_counts')->nullable();    // nếu cần: theo 2 số đầu của toàn dàn (ít dùng)
            $table->timestamps();

            $table->unique(['draw_date', 'station_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lottery_results');
    }
};
