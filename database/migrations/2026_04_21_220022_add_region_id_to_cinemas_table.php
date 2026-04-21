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
        Schema::table('cinemas', function (Blueprint $table) {
            $table->foreignId('region_id')->nullable()->after('cinema_name')->constrained('regions', 'region_id')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cinemas', function (Blueprint $table) {
            $table->dropForeign(['region_id']);
            $table->dropColumn('region_id');
        });
    }
};
