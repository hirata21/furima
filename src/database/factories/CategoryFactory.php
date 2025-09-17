<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CategoryFactory extends Factory
{
    use HasFactory;
    
    protected $model = Category::class;

    public function definition()
    {
        return [
            'name' => $this->faker->word(),
        ];
    }
}
