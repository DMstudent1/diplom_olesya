<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $price = $this->faker->randomFloat(2, 100, 10000);
        $oldPrice = $this->faker->optional(0.3)->randomFloat(2, $price + 100, $price + 5000);
        return [
            'name' => $this->faker->words(3, true),
            'category_id' => Category::factory(),
            'count'  => $this->faker->numberBetween(0, 100),
            'description'=> $this->faker->paragraphs(2, true),
            'old_price' => $oldPrice,
            'price' => $price,
            'characteristics' => json_encode([
                'color' => $this->faker->colorName(),
                'size' => $this->faker->randomElement(['S', 'M', 'L', 'XL']),
                'weight' => $this->faker->numberBetween(100, 5000) . 'g',
                'material' => $this->faker->randomElement(['Cotton', 'Polyester', 'Wool', 'Leather']),
                'brand' => $this->faker->company(),
                'country' => $this->faker->country(),
            ]),
        ];
    }
}
