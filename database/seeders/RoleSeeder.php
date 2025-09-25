<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $roles = [
            ['role_name' => 'Restaurant Owner'],
            ['role_name' => 'Manager'],
            ['role_name' => 'Staff'],
            ['role_name' => 'Waiter'],
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }
    }
}
