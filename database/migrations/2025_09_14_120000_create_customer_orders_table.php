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
        Schema::create('customer_orders', function (Blueprint $table) {
            $table->id('order_id');
            $table->unsignedBigInteger('restaurant_id');
            $table->string('order_number', 50)->unique();
            $table->enum('order_type', ['dine_in', 'takeout', 'delivery'])->default('dine_in');
            $table->enum('status', ['pending', 'confirmed', 'preparing', 'ready', 'served', 'completed', 'cancelled'])->default('pending');
            $table->string('customer_name', 100);
            $table->string('customer_phone', 20)->nullable();
            $table->text('customer_address')->nullable();
            $table->string('table_number', 10)->nullable();
            $table->decimal('subtotal', 10, 2);
            $table->decimal('tax_amount', 8, 2)->default(0);
            $table->decimal('discount_amount', 8, 2)->default(0);
            $table->decimal('delivery_fee', 8, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->text('notes')->nullable();
            $table->timestamp('order_time')->useCurrent();
            $table->timestamp('estimated_ready_time')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('served_by')->nullable(); // employee who served
            $table->timestamps();

            $table->foreign('restaurant_id')->references('id')->on('restaurant_data')->onDelete('cascade');
            $table->foreign('served_by')->references('id')->on('users')->onDelete('set null');

            $table->index(['restaurant_id', 'status']);
            $table->index(['order_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_orders');
    }
};