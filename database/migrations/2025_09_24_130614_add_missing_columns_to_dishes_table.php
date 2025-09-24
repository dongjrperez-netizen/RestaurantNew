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
            // Add columns that might be missing from the original migration
            if (!Schema::hasColumn('dishes', 'is_available')) {
                $table->boolean('is_available')->default(true);
            }
            if (!Schema::hasColumn('dishes', 'preparation_time')) {
                $table->integer('preparation_time')->nullable();
            }
            if (!Schema::hasColumn('dishes', 'serving_size')) {
                $table->decimal('serving_size', 8, 2)->nullable();
            }
            if (!Schema::hasColumn('dishes', 'serving_unit')) {
                $table->string('serving_unit')->nullable();
            }
            if (!Schema::hasColumn('dishes', 'calories')) {
                $table->integer('calories')->nullable();
            }
            if (!Schema::hasColumn('dishes', 'allergens')) {
                $table->json('allergens')->nullable();
            }
            if (!Schema::hasColumn('dishes', 'dietary_tags')) {
                $table->json('dietary_tags')->nullable();
            }
            if (!Schema::hasColumn('dishes', 'price')) {
                $table->decimal('price', 10, 2)->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dishes', function (Blueprint $table) {
            $table->dropColumn([
                'is_available',
                'preparation_time',
                'serving_size',
                'serving_unit',
                'calories',
                'allergens',
                'dietary_tags',
                'price'
            ]);
        });
    }
};
