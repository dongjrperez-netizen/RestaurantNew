<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop the existing enum constraint and recreate with paypal included
        DB::statement("ALTER TABLE supplier_payments MODIFY COLUMN payment_method ENUM('cash', 'bank_transfer', 'check', 'credit_card', 'paypal', 'online', 'other') DEFAULT 'bank_transfer'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to original enum values
        DB::statement("ALTER TABLE supplier_payments MODIFY COLUMN payment_method ENUM('cash', 'bank_transfer', 'check', 'credit_card', 'online', 'other') DEFAULT 'bank_transfer'");
    }
};
