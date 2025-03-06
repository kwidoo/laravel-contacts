<?php

namespace Kwidoo\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kwidoo\Contacts\Models\Contact;
use Kwidoo\Contacts\Tests\Fixtures\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Model>
 */
class ContactFactory extends Factory
{
    protected $model = Contact::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => $this->faker->uuid,
            'type' => 'email',
            'value' => $this->faker->unique()->safeEmail,
            'is_primary' => $this->faker->boolean,
            'is_verified' => $this->faker->boolean,
        ];
    }

    public function toUser($user): ContactFactory
    {
        return $this->state(function (array $attributes) use ($user) {
            return [
                'contactable_type' => get_class($user),
                'contactable_id' => $user->uuid,
            ];
        });
    }

    public function email(): ContactFactory
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'email',
                'value' => $this->faker->unique()->safeEmail,
            ];
        });
    }

    public function phone(): ContactFactory
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'phone',
                'value' => $this->faker->phoneNumber,
            ];
        });
    }
}
