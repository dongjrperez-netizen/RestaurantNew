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
        Schema::create('customer_order_items', function (Blueprint $table) {
            $table->id('order_item_id');
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('dish_id');
            $table->string('dish_name', 255); // Store dish name for historical record
            $table->decimal('unit_price', 8, 2);
            $table->integer('quantity');
            $table->decimal('total_price', 10, 2);
            $table->text('special_instructions')->nullable();
            $table->enum('status', ['pending', 'preparing', 'ready', 'served'])->default('pending');
            $table->boolean('inventory_deducted')->default(false); // Track if inventory was deducted
            $table->timestamp('inventory_deducted_at')->nullable();
            $table->timestamps();

            $table->foreign('order_id')->references('order_id')->on('customer_orders')->onDelete('cascade');
            $table->foreign('dish_id')->references('dish_id')->on('dishes')->onDelete('restrict');

            $table->index(['order_id']);
            $table->index(['dish_id']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_order_items');
    }
};