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
        Schema::create('subscriptionpackage_demos', function (Blueprint $table) {
            $table->id('plan_id');
            $table->string("plan_name");
            $table->decimal("plan_price", 10, 2);
            $table->integer("plan_duration")->default(1);
            $table->string('paypal_plan_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptionpackage_demos');
    }
};
