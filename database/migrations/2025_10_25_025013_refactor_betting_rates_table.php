<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('betting_rates', function (Blueprint $table) {
            $table->id();
            // null = default theo miền; có customer_id = giá riêng KH
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();

            // bac | trung | nam
            $table->string('region', 10)->index();

            // mã loại cược (tham chiếu BettingType::code, ví dụ: dau, duoi, de_duoi_4, bao_lo, da_thang, da_xien, xien, xiu_chu, bay_lo ...)
            $table->string('type_code', 50)->index();

            // tham số phụ để phân biệt biến thể cùng type
            // ví dụ: bao_lo => digits=2/3/4; xien => xien_size=2/3/4; da_xien => dai_count=2/3/4
            $table->unsignedTinyInteger('digits')->nullable();
            $table->unsignedTinyInteger('xien_size')->nullable();
            $table->unsignedTinyInteger('dai_count')->nullable();

            // Giá cò (hệ số mua, ví dụ 0.7); Lần ăn/payout (ví dụ 70, 600, 5000)
            $table->decimal('buy_rate', 8, 2)->default(0);   // “Giá”
            $table->decimal('payout', 12, 2)->default(0);    // “Lần ăn”

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Duy nhất theo khóa logic (NULL được tính là khác nhau – đủ dùng)
            $table->unique(['customer_id','region','type_code','digits','xien_size','dai_count'], 'uniq_rate_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('betting_rates');
    }
};
