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

            $table->foreignId('restaurant_id')->constrained('restaurant_data')->cascadeOnDelete();
            $table->unsignedBigInteger('supplier_id');
            $table->foreign('supplier_id')->references('supplier_id')->on('suppliers')->cascadeOnDelete();

            $table->dateTime('order_date')->useCurrent();
            $table->string('reference_no', 100)->nullable();
            $table->string('status', 50)->default('Pending'); // Pending, Received, Cancelled
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->timestamps();
                    
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('restaurant__orders');
    }
};
