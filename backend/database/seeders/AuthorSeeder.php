<?php

namespace Database\Seeders;

use App\Models\Author;
use App\Models\User;
use Illuminate\Database\Seeder;

class AuthorSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();

        $authors = [
            [
                'name' => 'Chetan Bhagat',
                'biography' => 'Chetan Bhagat is an Indian author and columnist. He has written several novels, including Five Point Someone, One Night @ the Call Center, and 2 States. Many of his books have been adapted into Bollywood films.',
                'country' => 'India',
            ],
            [
                'name' => 'Amish Tripathi',
                'biography' => 'Amish Tripathi is an Indian author known for his mythological fiction. He is the author of the Shiva Trilogy and the Ram Chandra Series.',
                'country' => 'India',
            ],
            [
                'name' => 'Jhumpa Lahiri',
                'biography' => 'Jhumpa Lahiri is a British-American author known for her short stories, novels, and essays in English and Italian. She won the Pulitzer Prize for Fiction in 2000 for Interpreter of Maladies.',
                'country' => 'United States',
            ],
            [
                'name' => 'Arundhati Roy',
                'biography' => 'Arundhati Roy is an Indian author best known for her novel The God of Small Things, which won the Man Booker Prize in 1997.',
                'country' => 'India',
            ],
            [
                'name' => 'R. K. Narayan',
                'biography' => 'Rasipuram Krishnaswami Iyer Narayanaswami was an Indian writer known for his works set in the fictional town of Malgudi. His notable works include The Guide and Swami and Friends.',
                'country' => 'India',
            ],
            [
                'name' => 'Khaled Hosseini',
                'biography' => 'Khaled Hosseini is an Afghan-American novelist and physician. His debut novel The Kite Runner was a major international bestseller.',
                'country' => 'United States',
            ],
            [
                'name' => 'Sudha Murty',
                'biography' => 'Sudha Murty is an Indian educator, author, and philanthropist. She is the chairperson of the Infosys Foundation and has written several books in Kannada and English.',
                'country' => 'India',
            ],
            [
                'name' => 'Ruskin Bond',
                'biography' => 'Ruskin Bond is an Indian author of British descent. He has written over 500 short stories, essays, and novels. He was awarded the Padma Shri in 1999.',
                'country' => 'India',
            ],
            [
                'name' => 'Paulo Coelho',
                'biography' => 'Paulo Coelho de Souza is a Brazilian lyricist and novelist. He is best known for his novel The Alchemist, which has been translated into 80 languages.',
                'country' => 'Brazil',
            ],
            [
                'name' => 'Dan Brown',
                'biography' => 'Daniel Gerhard Brown is an American author known for his thriller novels, including The Da Vinci Code and Angels & Demons.',
                'country' => 'United States',
            ],
            [
                'name' => 'Amitav Ghosh',
                'biography' => 'Amitav Ghosh is an Indian writer. He was awarded the Jnanpith Award in 2018, making him the first English-language writer to receive India\'s highest literary honour.',
                'country' => 'India',
            ],
            [
                'name' => 'Anita Desai',
                'biography' => 'Anita Desai is an Indian novelist and short story writer. She has been shortlisted for the Booker Prize three times and received the Sahitya Akademi Award.',
                'country' => 'India',
            ],
        ];

        foreach ($authors as $author) {
            Author::firstOrCreate(
                ['name' => $author['name']],
                array_merge($author, [
                    'created_by' => $user?->id,
                    'status' => true,
                ])
            );
        }
    }
}
