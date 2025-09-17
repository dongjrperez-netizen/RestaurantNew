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
        Schema::create('menu_versions', function (Blueprint $table) {
            $table->id('version_id');
            $table->foreignId('restaurant_id')->constrained('restaurant_data', 'id')->onDelete('cascade');
            $table->string('version_name', 100);
            $table->enum('menu_type', ['breakfast', 'lunch', 'dinner', 'all_day', 'special']);
            $table->timestamp('publish_date')->nullable();
            $table->boolean('is_current')->default(false);
            $table->timestamps();
            
            $table->index(['restaurant_id', 'is_current']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu_versions');
    }
};
