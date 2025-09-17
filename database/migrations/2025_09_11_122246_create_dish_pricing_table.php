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
        Schema::create('dish_pricing', function (Blueprint $table) {
            $table->id('pricing_id');
            $table->foreignId('dish_id')->constrained('dishes', 'dish_id')->onDelete('cascade');
            $table->enum('price_type', ['dine_in', 'takeout', 'delivery'])->default('dine_in');
            $table->decimal('base_price', 8, 2);
            $table->decimal('promotional_price', 8, 2)->nullable();
            $table->date('promo_start_date')->nullable();
            $table->date('promo_end_date')->nullable();
            $table->decimal('min_profit_margin', 5, 2)->default(20.00);
            $table->timestamps();
            
            $table->index(['dish_id', 'price_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dish_pricing');
    }
};
