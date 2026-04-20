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
        Schema::create('cinema_combos', function (Blueprint $table) {
            $table->integer('cinema_combo_id', true);
            $table->integer('cinema_id');
            $table->integer('combo_id');
            $table->decimal('price', 10, 2);
            $table->string('status', 50)->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cinema_combos');
    }
};
