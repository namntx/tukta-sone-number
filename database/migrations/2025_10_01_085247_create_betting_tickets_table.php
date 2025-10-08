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
        Schema::create('betting_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('betting_type_id')->constrained()->onDelete('cascade');
            $table->date('betting_date'); // Ngày cược
            $table->string('region'); // Miền cược (Bắc, Trung, Nam)
            $table->string('station'); // Đài cược
            $table->text('original_message'); // Tin nhắn cược gốc
            $table->text('parsed_message'); // Tin nhắn sau khi phân tích
            $table->json('betting_data'); // Dữ liệu cược chi tiết (số, loại, tiền)
            $table->enum('result', ['win', 'lose', 'pending'])->default('pending'); // Ăn/Thua/Chờ
            $table->decimal('bet_amount', 15, 2); // Số tiền cược
            $table->decimal('win_amount', 15, 2)->default(0); // Số tiền trúng
            $table->decimal('payout_amount', 15, 2)->default(0); // Số tiền phải trả
            $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');
            $table->timestamps();
            
            $table->index(['user_id', 'betting_date']);
            $table->index(['customer_id', 'betting_date']);
            $table->index(['betting_date', 'region']);
            $table->index(['result', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('betting_tickets');
    }
};
