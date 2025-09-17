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
        Schema::create('dish_costs', function (Blueprint $table) {
            $table->id('cost_id');
            $table->foreignId('dish_id')->constrained('dishes', 'dish_id')->onDelete('cascade');
            $table->decimal('ingredient_cost', 8, 2);
            $table->decimal('labor_cost', 8, 2)->default(0);
            $table->decimal('overhead_cost', 8, 2)->default(0);
            $table->decimal('total_cost', 8, 2);
            $table->timestamp('calculated_at')->useCurrent();
            $table->timestamps();
            
            $table->index(['dish_id', 'calculated_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dish_costs');
    }
};
