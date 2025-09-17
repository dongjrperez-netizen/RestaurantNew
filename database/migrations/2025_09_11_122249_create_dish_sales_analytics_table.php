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
        Schema::create('dish_sales_analytics', function (Blueprint $table) {
            $table->id('analytics_id');
            $table->foreignId('dish_id')->constrained('dishes', 'dish_id')->onDelete('cascade');
            $table->date('date');
            $table->integer('quantity_sold')->default(0);
            $table->decimal('total_revenue', 10, 2)->default(0);
            $table->decimal('total_cost', 10, 2)->default(0);
            $table->decimal('profit_margin', 5, 2)->nullable();
            $table->decimal('customer_rating', 3, 2)->nullable();
            $table->integer('preparation_time_avg')->nullable()->comment('actual vs estimated');
            $table->decimal('return_rate', 5, 2)->default(0);
            $table->timestamps();
            
            $table->unique(['dish_id', 'date']);
            $table->index(['date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dish_sales_analytics');
    }
};
