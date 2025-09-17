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
            // Remove old columns
            $table->dropColumn(['quantity_needed', 'unit_of_measure']);
            
            // Add new columns
            $table->decimal('quantity', 10, 3)->after('ingredient_id');
            $table->string('unit', 20)->after('quantity');
            $table->boolean('is_optional')->default(false)->after('unit');
            $table->text('preparation_note')->nullable()->after('is_optional');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dish_ingredients', function (Blueprint $table) {
            // Remove new columns
            $table->dropColumn(['quantity', 'unit', 'is_optional', 'preparation_note']);
            
            // Add back old columns
            $table->decimal('quantity_needed', 10, 4)->after('ingredient_id');
            $table->string('unit_of_measure')->nullable()->after('quantity_needed');
        });
    }
};
