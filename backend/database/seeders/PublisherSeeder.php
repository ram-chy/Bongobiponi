<?php

namespace Database\Seeders;

use App\Models\Publisher;
use App\Models\User;
use Illuminate\Database\Seeder;

class PublisherSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();

        $publishers = [
            [
                'name' => 'Penguin Random House India',
                'phone' => '01140123456',
                'email' => 'info@penguinrandomhouse.in',
                'address' => '7th Floor, 4378/4, Ansari Road, Daryaganj, New Delhi - 110002',
            ],
            [
                'name' => 'HarperCollins Publishers India',
                'phone' => '02240123456',
                'email' => 'info@harpercollins-india.com',
                'address' => 'World Trade Center, 14th Floor, Mumbai - 400005',
                'remarks' => 'Large catalog of fiction and non-fiction',
            ],
            [
                'name' => 'Oxford University Press India',
                'phone' => '01126704344',
                'email' => 'info@oup.co.in',
                'address' => 'YMC&A Building, 57/60 Janpath, New Delhi - 110001',
                'remarks' => 'Academic and reference books',
            ],
            [
                'name' => 'S. Chand Publishing',
                'phone' => '01126804344',
                'email' => 'info@schandpublishing.com',
                'address' => 'H-129, Kalkaji, New Delhi - 110019',
                'remarks' => 'Engineering and science textbooks',
            ],
            [
                'name' => 'Cengage Learning India',
                'phone' => '01204012345',
                'email' => 'india@cengage.com',
                'address' => '1th Floor, 5/6, Block B, Community Centre, Janakpuri, New Delhi - 110058',
                'remarks' => 'Computer science and IT books',
            ],
            [
                'name' => 'Rupa Publications',
                'phone' => '01125704344',
                'email' => 'info@rupapublications.com',
                'address' => '16, Community Centre, East of Kailash, New Delhi - 110065',
                'remarks' => 'Literary fiction and bestsellers',
            ],
        ];

        foreach ($publishers as $publisher) {
            Publisher::firstOrCreate(
                ['name' => $publisher['name']],
                array_merge($publisher, [
                    'created_by' => $user?->id,
                    'status' => true,
                ])
            );
        }
    }
}
