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
        Schema::create('betting_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('betting_type_id')->constrained()->onDelete('cascade');
            $table->decimal('win_rate', 8, 4); // Hệ số thu (ví dụ: 0.7 = 70%)
            $table->decimal('lose_rate', 8, 4); // Hệ số trả (ví dụ: 0.8 = 80%)
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique(['user_id', 'customer_id', 'betting_type_id']);
            $table->index(['user_id', 'customer_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('betting_rates');
    }
};
