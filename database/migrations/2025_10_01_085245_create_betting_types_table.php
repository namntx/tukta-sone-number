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
        Schema::create('betting_types', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Tên loại cược (Bao Lô Đảo, Bao Lô, Lô, etc.)
            $table->string('code')->unique(); // Mã loại cược
            $table->json('syntaxes'); // Danh sách cú pháp (array)
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->index(['is_active', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('betting_types');
    }
};
