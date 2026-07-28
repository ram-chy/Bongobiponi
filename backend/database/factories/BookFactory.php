<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Book> */
class BookFactory extends Factory
{
    protected $model = Book::class;

    public function definition(): array
    {
        $purchasePrice = fake()->randomFloat(2, 100, 2000);
        $sellingPrice = $purchasePrice * fake()->randomFloat(2, 1.3, 2.5);

        return [
            'created_by' => User::factory(),
            'isbn' => fake()->unique()->numerify('978-#-##-######-#'),
            'barcode' => fake()->unique()->numerify('##############'),
            'title' => fake()->words(3, true),
            'subtitle' => fake()->optional(0.4)->words(4, true),
            'publisher_id' => null,
            'category_id' => null,
            'edition' => fake()->optional(0.5)->randomElement(['1st', '2nd', '3rd', '4th', 'Revised', 'Extended']),
            'language' => fake()->optional(0.8)->randomElement(['English', 'Hindi', 'Bengali', 'Tamil', 'Telugu', 'Marathi']),
            'purchase_price' => $purchasePrice,
            'selling_price' => round($sellingPrice, 2),
            'minimum_stock' => fake()->numberBetween(3, 20),
            'description' => fake()->optional(0.7)->paragraph(),
            'cover_image' => fake()->optional(0.3)->imageUrl(640, 480, 'book'),
        ];
    }
}
