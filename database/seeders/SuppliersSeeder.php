<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SuppliersSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('suppliers')->insert([
            [
                'SupplierName' => 'Fresh Farms',
                'ContactNumber' => '09180000001',
                'Address' => 'Quezon City'
            ],
            [
                'SupplierName' => 'Golden Foods Distributor',
                'ContactNumber' => '09180000002',
                'Address' => 'Makati'
            ],
        ]);
    }
}
