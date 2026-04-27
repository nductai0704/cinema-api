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
        Schema::table('showtimes', function (Blueprint $table) {
            $table->decimal('price_standard', 10, 2)->default(0)->after('ticket_price');
            $table->decimal('price_vip', 10, 2)->default(0)->after('price_standard');
            $table->decimal('price_double', 10, 2)->default(0)->after('price_vip');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('showtimes', function (Blueprint $table) {
            $table->dropColumn(['price_standard', 'price_vip', 'price_double']);
        });
    }
};
