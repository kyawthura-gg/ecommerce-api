<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $name = 'Testing for duplicate name';
        return [
            'category_id' => 1,
            'user_id' => 1,
            'slug' => Str::slug($name, '-'),
            'name' => $name,
            'description' => Str::random(20),
            'price' => 1000,
            'image' => '/images/simple.jpg',
            'brand' => Str::random(7),
            'count_stock' => 10,
            'rating' => 0,
            'num_reviews' => 0,
        ];
    }
}
