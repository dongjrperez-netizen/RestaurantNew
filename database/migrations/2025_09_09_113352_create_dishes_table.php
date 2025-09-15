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
        Schema::create('dishes', function (Blueprint $table) {
            $table->id('dish_id');
            $table->unsignedBigInteger('restaurant_id');
            $table->string('dish_name');
            $table->text('description')->nullable();
            $table->decimal('selling_price', 10, 2);
            $table->string('category')->nullable();
            $table->boolean('is_available')->default(true);
            $table->timestamps();

            $table->foreign('restaurant_id')->references('id')->on('restaurant_data')->onDelete('cascade');
            $table->index(['restaurant_id', 'is_available']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dishes');
    }
};
