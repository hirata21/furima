<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\UserAddress;
use App\Models\User;

class UserAddressFactory extends Factory
{

    protected $model = UserAddress::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id'  => User::factory(),
            'postcode' => $this->faker->regexify('\d{3}-\d{4}'),
            'address'  => $this->faker->address(),
            'building' => $this->faker->optional()->secondaryAddress(),
        ];
    }
}
