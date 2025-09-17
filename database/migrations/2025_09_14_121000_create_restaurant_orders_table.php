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
        Schema::create('restaurant_orders', function (Blueprint $table) {
            $table->id('order_id');
            $table->unsignedBigInteger('restaurant_id');
            $table->unsignedBigInteger('supplier_id');
            $table->string('reference_no')->nullable();
            $table->enum('status', ['pending', 'confirmed', 'shipped', 'received', 'cancelled'])->default('pending');
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->timestamp('order_date')->useCurrent();
            $table->timestamp('expected_delivery')->nullable();
            $table->timestamp('received_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('restaurant_id')->references('id')->on('restaurant_data')->onDelete('cascade');
            $table->foreign('supplier_id')->references('supplier_id')->on('suppliers')->onDelete('cascade');

            $table->index(['restaurant_id', 'status']);
            $table->index(['order_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('restaurant_orders');
    }
};