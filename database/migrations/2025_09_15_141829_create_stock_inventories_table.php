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
        Schema::create('stock_inventories', function (Blueprint $table) {
            $table->id('stock_id');
            $table->unsignedBigInteger('restaurant_id');
            $table->unsignedBigInteger('ingredient_id');
            $table->string('batch_number')->nullable();
            $table->decimal('quantity', 10, 2);
            $table->string('unit');
            $table->decimal('cost_per_unit', 8, 2);
            $table->decimal('total_cost', 10, 2);
            $table->date('expiry_date')->nullable();
            $table->date('received_date');
            $table->string('supplier_name')->nullable();
            $table->string('purchase_order_number')->nullable();
            $table->enum('status', ['available', 'transferred', 'expired', 'damaged'])->default('available');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('restaurant_id')->references('id')->on('restaurant_data')->onDelete('cascade');
            $table->foreign('ingredient_id')->references('ingredient_id')->on('ingredients')->onDelete('cascade');

            $table->index(['restaurant_id', 'ingredient_id']);
            $table->index(['status', 'expiry_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_inventories');
    }
};