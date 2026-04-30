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
        Schema::table('combos', function (Blueprint $table) {
            $table->string('target_audience')->nullable()->after('description')->comment('Đối tượng: Người lớn, trẻ em, gia đình...');
        });
    }

    public function down(): void
    {
        Schema::table('combos', function (Blueprint $table) {
            $table->dropColumn('target_audience');
        });
    }
};
