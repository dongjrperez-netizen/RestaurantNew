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
         Schema::create('suppliers', function (Blueprint $table) {
            $table->id('supplier_id');
            $table->unsignedBigInteger('restaurant_id');
            $table->foreign('restaurant_id')->references('id')->on('restaurant_data')->onDelete('cascade');
            $table->string('supplier_name', 150);
            $table->string('contact_number', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('password');
            $table->string('address', 255)->nullable();
            $table->timestamp('email_verified_at')->nullable()->after('password');
            $table->string('business_registration')->nullable()->after('email');
            $table->string('tax_id')->nullable()->after('business_registration');
            $table->enum('payment_terms', ['COD', 'NET_7', 'NET_15', 'NET_30', 'NET_60', 'NET_90'])->default('NET_30')->after('tax_id');
            $table->decimal('credit_limit', 12, 2)->nullable()->after('payment_terms');
            $table->text('notes')->nullable()->after('credit_limit');
            $table->boolean('is_active')->default(true)->after('notes');

            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
