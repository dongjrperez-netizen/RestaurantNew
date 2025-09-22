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
        Schema::table('table_reservations', function (Blueprint $table) {
            $table->datetime('actual_arrival_time')->nullable()->after('duration_minutes');
            $table->datetime('dining_start_time')->nullable()->after('actual_arrival_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('table_reservations', function (Blueprint $table) {
            $table->dropColumn(['actual_arrival_time', 'dining_start_time']);
        });
    }
};
