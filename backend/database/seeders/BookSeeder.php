<?php

namespace Database\Seeders;

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Publisher;
use App\Models\User;
use Illuminate\Database\Seeder;

class BookSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();

        $publishers = Publisher::all()->keyBy('name');
        $categories = Category::all()->keyBy('name');
        $authors = Author::all()->keyBy('name');

        $books = [
            [
                'title' => 'Five Point Someone',
                'subtitle' => 'What not to do at IIT',
                'isbn' => '978-81-291-0530-1',
                'barcode' => '9788129105301',
                'publisher' => 'Rupa Publications',
                'category' => 'Contemporary Fiction',
                'authors' => ['Chetan Bhagat'],
                'edition' => '2nd',
                'language' => 'English',
                'purchase_price' => 150.00,
                'selling_price' => 250.00,
                'minimum_stock' => 10,
                'description' => 'Three friends arrive at IIT with high hopes. Their journey through the pressures of IIT academics tests their friendship and ambitions.',
            ],
            [
                'title' => 'The Immortals of Meluha',
                'isbn' => '978-81-291-0893-8',
                'barcode' => '9788129108938',
                'publisher' => 'Rupa Publications',
                'category' => 'Science Fiction & Fantasy',
                'authors' => ['Amish Tripathi'],
                'edition' => '1st',
                'language' => 'English',
                'purchase_price' => 199.00,
                'selling_price' => 350.00,
                'minimum_stock' => 8,
                'description' => 'The first book in the Shiva Trilogy. A mysterious man arrives in the land of Meluha and is soon recognised as the Neelkanth.',
            ],
            [
                'title' => 'The Namesake',
                'isbn' => '978-0-618-48256-1',
                'barcode' => '9780618482561',
                'publisher' => 'HarperCollins Publishers India',
                'category' => 'Contemporary Fiction',
                'authors' => ['Jhumpa Lahiri'],
                'edition' => '1st',
                'language' => 'English',
                'purchase_price' => 350.00,
                'selling_price' => 599.00,
                'minimum_stock' => 5,
                'description' => 'The story of Gogol Ganguli, the American-born son of Bengali immigrants, as he navigates between two cultures.',
            ],
            [
                'title' => 'The God of Small Things',
                'isbn' => '978-0-679-76104-9',
                'barcode' => '9780679761049',
                'publisher' => 'Penguin Random House India',
                'category' => 'Classic Literature',
                'authors' => ['Arundhati Roy'],
                'edition' => '1st',
                'language' => 'English',
                'purchase_price' => 299.00,
                'selling_price' => 499.00,
                'minimum_stock' => 5,
                'description' => 'Winner of the Man Booker Prize. A story of twins Estha and Rahel, and the events that shape their childhood in Kerala.',
            ],
            [
                'title' => 'The Guide',
                'isbn' => '978-0-14-306215-8',
                'barcode' => '9780143062158',
                'publisher' => 'Penguin Random House India',
                'category' => 'Classic Literature',
                'authors' => ['R. K. Narayan'],
                'edition' => '1st',
                'language' => 'English',
                'purchase_price' => 175.00,
                'selling_price' => 299.00,
                'minimum_stock' => 6,
                'description' => 'Winner of the Sahitya Akademi Award. The story of Raju, a tourist guide who becomes a spiritual guru.',
            ],
            [
                'title' => 'The Kite Runner',
                'isbn' => '978-1-59448-000-3',
                'barcode' => '9781594480003',
                'publisher' => 'HarperCollins Publishers India',
                'category' => 'Contemporary Fiction',
                'authors' => ['Khaled Hosseini'],
                'edition' => '1st',
                'language' => 'English',
                'purchase_price' => 325.00,
                'selling_price' => 599.00,
                'minimum_stock' => 7,
                'description' => 'A story of friendship, betrayal, and redemption set against the backdrop of a changing Afghanistan.',
            ],
            [
                'title' => 'Wise and Otherwise',
                'isbn' => '978-81-7276-478-5',
                'barcode' => '9788172764785',
                'publisher' => 'Penguin Random House India',
                'category' => 'Non-Fiction',
                'authors' => ['Sudha Murty'],
                'edition' => '1st',
                'language' => 'English',
                'purchase_price' => 150.00,
                'selling_price' => 250.00,
                'minimum_stock' => 10,
                'description' => 'A collection of heartwarming and thought-provoking stories from Sudha Murty\'s travels across India.',
            ],
            [
                'title' => 'The Blue Umbrella',
                'isbn' => '978-0-14-333379-4',
                'barcode' => '9780143333794',
                'publisher' => 'Penguin Random House India',
                'category' => 'Storybooks',
                'authors' => ['Ruskin Bond'],
                'edition' => '1st',
                'language' => 'English',
                'purchase_price' => 100.00,
                'selling_price' => 175.00,
                'minimum_stock' => 12,
                'description' => 'A charming children\'s novella about a young girl and her prized blue umbrella in a Himalayan village.',
            ],
            [
                'title' => 'The Alchemist',
                'isbn' => '978-0-06-251140-9',
                'barcode' => '9780062511409',
                'publisher' => 'HarperCollins Publishers India',
                'category' => 'Science Fiction & Fantasy',
                'authors' => ['Paulo Coelho'],
                'edition' => '1st',
                'language' => 'English',
                'purchase_price' => 250.00,
                'selling_price' => 450.00,
                'minimum_stock' => 8,
                'description' => 'A philosophical novel about a young shepherd who travels from Spain to Egypt in search of treasure.',
            ],
            [
                'title' => 'The Da Vinci Code',
                'isbn' => '978-0-307-47427-8',
                'barcode' => '9780307474278',
                'publisher' => 'Penguin Random House India',
                'category' => 'Mystery & Thriller',
                'authors' => ['Dan Brown'],
                'edition' => '1st',
                'language' => 'English',
                'purchase_price' => 325.00,
                'selling_price' => 550.00,
                'minimum_stock' => 10,
                'description' => 'A thriller following Robert Langdon as he investigates a murder in the Louvre and uncovers a religious mystery.',
            ],
            [
                'title' => 'The Shadow Lines',
                'isbn' => '978-0-14-012188-1',
                'barcode' => '9780140121881',
                'publisher' => 'Penguin Random House India',
                'category' => 'Classic Literature',
                'authors' => ['Amitav Ghosh'],
                'edition' => '1st',
                'language' => 'English',
                'purchase_price' => 225.00,
                'selling_price' => 399.00,
                'minimum_stock' => 5,
                'description' => 'A novel exploring memory, time, and borders through the eyes of a narrator recalling his childhood in Calcutta.',
            ],
            [
                'title' => 'Crying in H Mart',
                'isbn' => '978-1-101-90613-3',
                'barcode' => '9781101906133',
                'publisher' => 'Penguin Random House India',
                'category' => 'Biography & Autobiography',
                'authors' => ['Jhumpa Lahiri'],
                'edition' => '1st',
                'language' => 'English',
                'purchase_price' => 399.00,
                'selling_price' => 699.00,
                'minimum_stock' => 4,
                'description' => 'A memoir about the author\'s relationship with her Korean mother and the role of food in cultural identity.',
            ],
            [
                'title' => 'Introduction to Algorithms',
                'isbn' => '978-0-262-04630-5',
                'barcode' => '9780262046305',
                'publisher' => 'Cengage Learning India',
                'category' => 'Engineering',
                'authors' => ['Amitav Ghosh'],
                'edition' => '4th',
                'language' => 'English',
                'purchase_price' => 650.00,
                'selling_price' => 1099.00,
                'minimum_stock' => 6,
                'description' => 'The definitive textbook on algorithms, widely used in computer science courses worldwide.',
            ],
            [
                'title' => 'Hazaar Chaurasi Ki Maa',
                'isbn' => '978-81-7070-121-9',
                'barcode' => '9788170701219',
                'publisher' => 'Rupa Publications',
                'category' => 'Contemporary Fiction',
                'authors' => ['Arundhati Roy', 'Sudha Murty'],
                'edition' => '1st',
                'language' => 'Hindi',
                'purchase_price' => 125.00,
                'selling_price' => 200.00,
                'minimum_stock' => 8,
                'description' => 'A mother navigates the aftermath of the Naxalite movement in Bengal while waiting for her son to return.',
            ],
            [
                'title' => 'Midnight\'s Children',
                'isbn' => '978-0-8129-7653-3',
                'barcode' => '9780812976533',
                'publisher' => 'Penguin Random House India',
                'category' => 'Classic Literature',
                'authors' => ['Arundhati Roy', 'Salman Rushdie'],
                'edition' => 'Revised',
                'language' => 'English',
                'purchase_price' => 399.00,
                'selling_price' => 699.00,
                'minimum_stock' => 4,
                'description' => 'A landmark novel of Indian literature. Children born at the stroke of midnight on India\'s independence develop special powers.',
            ],
        ];

        foreach ($books as $bookData) {
            $authorNames = $bookData['authors'];
            unset($bookData['authors']);

            $publisherName = $bookData['publisher'];
            $categoryName = $bookData['category'];
            unset($bookData['publisher']);
            unset($bookData['category']);

            $bookData['publisher_id'] = $publishers->get($publisherName)?->id;
            $bookData['category_id'] = $categories->get($categoryName)?->id;
            $bookData['created_by'] = $user?->id;
            $bookData['status'] = true;

            $book = Book::firstOrCreate(
                ['isbn' => $bookData['isbn']],
                $bookData
            );

            $authorIds = [];
            foreach ($authorNames as $authorName) {
                $author = $authors->get($authorName);
                if ($author) {
                    $authorIds[] = $author->id;
                }
            }

            if (!empty($authorIds)) {
                $book->authors()->syncWithoutDetaching($authorIds);
            }
        }
    }
}
