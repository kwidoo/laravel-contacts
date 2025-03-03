<?php

namespace Kwidoo\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kwidoo\Contacts\Tests\Fixtures\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Model>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => $this->faker->uuid,
        ];
    }
}
