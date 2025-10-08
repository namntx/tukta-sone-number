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
        Schema::create('lottery_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('day_of_week'); // Thứ Hai, Thứ Ba, etc.
            $table->string('region'); // Bắc, Trung, Nam
            $table->string('main_station'); // Đài chính
            $table->json('sub_stations'); // Đài phụ (array)
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique(['day_of_week', 'region']);
            $table->index(['day_of_week', 'is_active']);
            $table->index(['region', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lottery_schedules');
    }
};