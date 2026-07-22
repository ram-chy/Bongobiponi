<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::firstOrCreate(['name' => 'Admin', 'slug' => 'admin']);
        Role::firstOrCreate(['name' => 'Manager', 'slug' => 'manager']);
        Role::firstOrCreate(['name' => 'Regular User', 'slug' => 'regular_user']);
    }
}
