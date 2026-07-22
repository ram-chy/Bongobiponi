<?php

namespace Database\Seeders;

use App\Models\User;
use Database\Seeders\ExpenseCategorySeeder;
use Database\Seeders\QuotationSeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call(RoleSeeder::class);

        User::factory()->create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin@example.com',
            'role_id' => 1,
        ]);

        User::factory()->create([
            'first_name' => 'Manager',
            'last_name' => 'User',
            'email' => 'manager@example.com',
            'role_id' => 2,
        ]);

        User::factory()->create([
            'first_name' => 'Regular',
            'last_name' => 'User',
            'email' => 'regular@example.com',
            'role_id' => 3,
        ]);

        $this->call(QuotationSeeder::class);
        $this->call(ExpenseCategorySeeder::class);

        // Master Data seeders (order matters: Books depends on Publishers, Categories, Authors)
        $this->call(PublisherSeeder::class);
        $this->call(CategorySeeder::class);
        $this->call(AuthorSeeder::class);
        $this->call(SupplierSeeder::class);
        $this->call(BookSeeder::class);
    }
}
