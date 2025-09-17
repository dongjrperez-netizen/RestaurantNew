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
        Schema::create('kitchen_inventories', function (Blueprint $table) {
            $table->id('kitchen_stock_id');
            $table->unsignedBigInteger('restaurant_id');
            $table->unsignedBigInteger('ingredient_id');
            $table->unsignedBigInteger('stock_id'); // Reference to stock_inventories
            $table->decimal('quantity_transferred', 10, 2);
            $table->decimal('quantity_remaining', 10, 2);
            $table->string('unit');
            $table->date('transfer_date');
            $table->date('expiry_date')->nullable();
            $table->unsignedBigInteger('transferred_by'); // User who transferred
            $table->enum('status', ['active', 'used_up', 'expired', 'returned'])->default('active');
            $table->text('transfer_notes')->nullable();
            $table->timestamps();

            $table->foreign('restaurant_id')->references('id')->on('restaurant_data')->onDelete('cascade');
            $table->foreign('ingredient_id')->references('ingredient_id')->on('ingredients')->onDelete('cascade');
            $table->foreign('stock_id')->references('stock_id')->on('stock_inventories')->onDelete('cascade');
            $table->foreign('transferred_by')->references('id')->on('users')->onDelete('cascade');

            $table->index(['restaurant_id', 'ingredient_id']);
            $table->index(['status', 'expiry_date']);
            $table->index('transfer_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kitchen_inventories');
    }
};