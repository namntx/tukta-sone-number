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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('phone')->unique();
            $table->decimal('total_win_amount', 15, 2)->default(0); // Tổng tiền ăn
            $table->decimal('total_lose_amount', 15, 2)->default(0); // Tổng tiền thua
            $table->decimal('daily_win_amount', 15, 2)->default(0); // Tiền ăn hôm nay
            $table->decimal('daily_lose_amount', 15, 2)->default(0); // Tiền thua hôm nay
            $table->decimal('monthly_win_amount', 15, 2)->default(0); // Tiền ăn tháng này
            $table->decimal('monthly_lose_amount', 15, 2)->default(0); // Tiền thua tháng này
            $table->decimal('yearly_win_amount', 15, 2)->default(0); // Tiền ăn năm này
            $table->decimal('yearly_lose_amount', 15, 2)->default(0); // Tiền thua năm này
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['user_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
