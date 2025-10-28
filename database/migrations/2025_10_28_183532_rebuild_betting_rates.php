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
        Schema::table('betting_rates', function (Blueprint $table) {
            // Region code chuẩn: bac|trung|nam (bắt buộc, không NULL)
            if (!Schema::hasColumn('betting_rates', 'region')) {
                $table->string('region', 10)->default('bac')->after('betting_type_id');
            }

            // Chuẩn hoá trường giá:
            // payout_multiplier: "đánh 1 ăn X" (VD loto MB ăn 70)
            if (!Schema::hasColumn('betting_rates', 'payout_multiplier')) {
                $table->decimal('payout_multiplier', 10, 4)->nullable()->after('region');
            }
            // commission_rate: chiết khấu/giảm trừ trên tiền vào (0.00..1.00)
            if (!Schema::hasColumn('betting_rates', 'commission_rate')) {
                $table->decimal('commission_rate', 6, 4)->nullable()->after('payout_multiplier');
            }

            // Cột phát sinh để UNIQUE kể cả khi customer_id = NULL (MySQL 8)
            if (!Schema::hasColumn('betting_rates', 'customer_key')) {
                $table->unsignedBigInteger('customer_key')->storedAs('IFNULL(`customer_id`, 0)')->after('customer_id');
            }

            // Cờ hoạt động
            if (!Schema::hasColumn('betting_rates', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('commission_rate');
            }

            // Dọn unique cũ (nếu có)
            try { $table->dropUnique('betting_rates_user_type_customer_unique'); } catch (\Throwable $e) {}
        });

        // Tạo UNIQUE mới bảo đảm 1 dòng duy nhất cho (user, type, region, customer|default)
        Schema::table('betting_rates', function (Blueprint $table) {
            $table->unique(['user_id','betting_type_id','region','customer_key'], 'br_unique_user_type_region_customerkey');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('betting_rates', function (Blueprint $table) {
            $table->dropUnique('br_unique_user_type_region_customerkey');
        });
    }
};
