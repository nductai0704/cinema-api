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
            if (Schema::hasColumn('cinemas', 'city')) {
                $table->dropColumn('city');
            }
            if (Schema::hasColumn('cinemas', 'district')) {
                $table->dropColumn('district');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cinemas', function (Blueprint $table) {
            $table->string('city')->nullable();
            $table->string('district')->nullable();
        });
    }
};
