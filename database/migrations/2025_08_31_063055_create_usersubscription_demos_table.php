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
        Schema::create('usersubscription_demos', function (Blueprint $table) {
            $table->id('userSubscription_id');
            $table->dateTime('subscription_startDate');
            $table->dateTime('subscription_endDate');
            $table->integer('remaining_minutes')->default(0); // demo uses minutes
            $table->string('subscription_status')->default('active');

            $table->foreignId('plan_id')->constrained('subscriptionpackage_demos', 'plan_id')->onDelete('cascade');
            $table->foreignId('restaurant_id')->constrained('restaurant_data')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usersubscription_demos');
    }
};
