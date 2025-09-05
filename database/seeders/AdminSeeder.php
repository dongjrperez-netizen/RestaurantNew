<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Administrator;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        Administrator::create([
            'email' => 'admin@example.com',
            'password' => Hash::make('admin123'),
        ]);
    }
}
