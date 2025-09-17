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
        Schema::table('dish_ingredients', function (Blueprint $table) {
            $table->string('ingredient_name')->after('ingredient_id');
            $table->decimal('cost_per_unit', 8, 2)->nullable()->after('unit');
            $table->unsignedBigInteger('ingredient_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dish_ingredients', function (Blueprint $table) {
            $table->dropColumn(['ingredient_name', 'cost_per_unit']);
            $table->unsignedBigInteger('ingredient_id')->nullable(false)->change();
        });
    }
};
