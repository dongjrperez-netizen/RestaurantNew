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
        Schema::table('dishes', function (Blueprint $table) {
            if (Schema::hasColumn('dishes', 'image_url')) {
                $table->dropColumn('image_url');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dishes', function (Blueprint $table) {
            if (!Schema::hasColumn('dishes', 'image_url')) {
                $table->string('image_url', 500)->nullable();
            }
        });
    }
};
