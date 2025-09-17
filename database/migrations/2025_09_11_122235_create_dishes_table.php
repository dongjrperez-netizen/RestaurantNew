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
            $table->foreignId('restaurant_id')->constrained('restaurant_data', 'id')->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained('menu_categories', 'category_id')->onDelete('set null');
            $table->string('dish_name', 255);
            $table->text('description')->nullable();
            $table->integer('preparation_time')->nullable()->comment('in minutes');
            $table->decimal('serving_size', 8, 2)->nullable();
            $table->string('serving_unit', 20)->nullable();
            $table->string('image_url', 500)->nullable();
            $table->integer('calories')->nullable();
            $table->json('allergens')->nullable();
            $table->json('dietary_tags')->nullable()->comment('vegetarian, vegan, gluten-free');
            $table->enum('status', ['draft', 'active', 'inactive', 'archived'])->default('draft');
            $table->foreignId('created_by')->constrained('users', 'id');
            $table->timestamps();
            
            $table->index(['restaurant_id', 'status']);
            $table->index(['category_id']);
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
