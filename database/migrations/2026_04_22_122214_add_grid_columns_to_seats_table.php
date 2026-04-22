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
        Schema::table('seats', function (Blueprint $table) {
            $table->integer('grid_x')->nullable()->after('seat_type');
            $table->integer('grid_y')->nullable()->after('grid_x');
            $table->string('pair_uuid', 50)->nullable()->after('grid_y');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seats', function (Blueprint $table) {
            $table->dropColumn(['grid_x', 'grid_y', 'pair_uuid']);
        });
    }
};
