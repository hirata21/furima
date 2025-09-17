<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

class ItemFactory extends Factory
{
    protected $model = Item::class;

    public function definition()
    {
        return [
            'user_id'    => User::factory(),
            'name'       => $this->faker->words(2, true),
            'brand'      => $this->faker->optional()->company(),
            'description' => $this->faker->paragraph(),
            'image_path' => 'items/' . Str::uuid() . '.jpg',
            'price'      => $this->faker->numberBetween(300, 50000),
            'condition'  => $this->faker->randomElement(['新品', '目立った傷や汚れなし', 'やや傷や汚れあり', '全体的に状態が悪い']),
            'is_sold'    => false,
        ];
    }
}
