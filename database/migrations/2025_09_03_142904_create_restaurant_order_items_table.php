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
           Schema::create('restaurant_order_items', function (Blueprint $table) {
            $table->id('order_item_id');

            $table->unsignedBigInteger('order_id');
            $table->foreign('order_id')->references('order_id')->on('restaurant_orders')->cascadeOnDelete();

            $table->unsignedBigInteger('ingredient_id');
            $table->foreign('ingredient_id')->references('ingredient_id')->on('ingredients')->cascadeOnDelete();

            $table->string('item_type', 20);
            $table->string('unit', 50);     
            $table->decimal('quantity', 12, 2);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('total_price', 12, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('restaurant_order_items');
    }
};
