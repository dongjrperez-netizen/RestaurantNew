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
        Schema::table('dishes', function (Blueprint $table) {
            // Remove old columns
            $table->dropColumn(['selling_price', 'category', 'is_available']);
            
            // Add new columns for menu management
            $table->foreignId('category_id')->nullable()->after('restaurant_id')->constrained('menu_categories', 'category_id')->onDelete('set null');
            $table->integer('preparation_time')->nullable()->after('description')->comment('in minutes');
            $table->decimal('serving_size', 8, 2)->nullable()->after('preparation_time');
            $table->string('serving_unit', 20)->nullable()->after('serving_size');
            $table->string('image_url', 500)->nullable()->after('serving_unit');
            $table->integer('calories')->nullable()->after('image_url');
            $table->json('allergens')->nullable()->after('calories');
            $table->json('dietary_tags')->nullable()->after('allergens')->comment('vegetarian, vegan, gluten-free');
            $table->enum('status', ['draft', 'active', 'inactive', 'archived'])->default('draft')->after('dietary_tags');
            $table->foreignId('created_by')->after('status')->constrained('users', 'id');
            
            // Add indexes
            $table->index(['restaurant_id', 'status']);
            $table->index(['category_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dishes', function (Blueprint $table) {
            // Remove new columns
            $table->dropForeign(['category_id']);
            $table->dropForeign(['created_by']);
            $table->dropIndex(['restaurant_id', 'status']);
            $table->dropIndex(['category_id']);
            
            $table->dropColumn([
                'category_id', 'preparation_time', 'serving_size', 'serving_unit',
                'image_url', 'calories', 'allergens', 'dietary_tags', 'status', 'created_by'
            ]);
            
            // Add back old columns
            $table->decimal('selling_price', 10, 2)->after('description');
            $table->string('category')->nullable()->after('selling_price');
            $table->boolean('is_available')->default(true)->after('category');
        });
    }
};
