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
        Schema::create('seat_layouts', function (Blueprint $table) {
            $table->id('layout_id');
            $table->foreignId('cinema_id')->constrained('cinemas', 'cinema_id')->cascadeOnDelete();
            $table->string('name', 255);
            $table->integer('row_count')->default(0);
            $table->integer('column_count')->default(0);
            $table->json('layout_data')->nullable(); // Có thể dùng json
            $table->string('status', 50)->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seat_layouts');
    }
};
