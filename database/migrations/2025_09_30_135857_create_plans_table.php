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
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Tên gói (1 tháng, 3 tháng, 1 năm, Custom)
            $table->string('slug')->unique(); // URL-friendly name
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2); // Giá tiền
            $table->integer('duration_days'); // Số ngày (30, 90, 365, custom)
            $table->boolean('is_active')->default(true);
            $table->boolean('is_custom')->default(false); // Gói custom
            $table->json('features')->nullable(); // Các tính năng của gói
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};