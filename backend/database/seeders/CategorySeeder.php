<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();

        $parentCategories = [
            [
                'name' => 'Fiction',
                'description' => 'Novels, short stories, and literary fiction',
            ],
            [
                'name' => 'Non-Fiction',
                'description' => 'Biographies, memoirs, essays, and general non-fiction',
            ],
            [
                'name' => 'Academic',
                'description' => 'Textbooks and reference materials for schools and colleges',
            ],
            [
                'name' => 'Children & Young Adult',
                'description' => 'Books for children and young adult readers',
            ],
            [
                'name' => 'Self-Help',
                'description' => 'Personal development, motivation, and wellness',
            ],
            [
                'name' => 'Science & Technology',
                'description' => 'Science, computing, engineering, and technology',
            ],
            [
                'name' => 'Comics & Graphic Novels',
                'description' => 'Comics, manga, and graphic novels',
            ],
        ];

        $parentIdMap = [];

        foreach ($parentCategories as $cat) {
            $record = Category::firstOrCreate(
                ['name' => $cat['name']],
                array_merge($cat, [
                    'created_by' => $user?->id,
                ])
            );
            $parentIdMap[$cat['name']] = $record->id;
        }

        $childCategories = [
            ['name' => 'Contemporary Fiction', 'parent' => 'Fiction', 'description' => 'Modern and contemporary literary fiction'],
            ['name' => 'Classic Literature', 'parent' => 'Fiction', 'description' => 'Timeless literary classics'],
            ['name' => 'Mystery & Thriller', 'parent' => 'Fiction', 'description' => 'Crime, mystery, and suspense novels'],
            ['name' => 'Romance', 'parent' => 'Fiction', 'description' => 'Romantic fiction and love stories'],
            ['name' => 'Science Fiction & Fantasy', 'parent' => 'Fiction', 'description' => 'Speculative fiction, sci-fi, and fantasy'],
            ['name' => 'Biography & Autobiography', 'parent' => 'Non-Fiction', 'description' => 'Life stories of notable people'],
            ['name' => 'History', 'parent' => 'Non-Fiction', 'description' => 'Historical accounts and analysis'],
            ['name' => 'Philosophy', 'parent' => 'Non-Fiction', 'description' => 'Philosophical texts and thought'],
            ['name' => 'Engineering', 'parent' => 'Academic', 'description' => 'Engineering textbooks and references'],
            ['name' => 'Commerce & Management', 'parent' => 'Academic', 'description' => 'Business, MBA, and commerce books'],
            ['name' => 'Competitive Exams', 'parent' => 'Academic', 'description' => 'Preparation materials for competitive examinations'],
            ['name' => 'Storybooks', 'parent' => 'Children & Young Adult', 'description' => 'Illustrated stories and chapter books'],
            ['name' => 'Manga & Graphic Novels', 'parent' => 'Comics & Graphic Novels', 'description' => 'Japanese manga and graphic storytelling'],
        ];

        foreach ($childCategories as $cat) {
            Category::firstOrCreate(
                ['name' => $cat['name']],
                [
                    'created_by' => $user?->id,
                    'parent_id' => $parentIdMap[$cat['parent']],
                    'description' => $cat['description'],
                ]
            );
        }
    }
}
