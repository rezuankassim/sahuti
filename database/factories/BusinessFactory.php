<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Business>
 */
class BusinessFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'phone_number' => fake()->unique()->numerify('60##########'),
            'name' => fake()->company(),
            'services' => [
                ['name' => 'Standard Service', 'price' => '50'],
                ['name' => 'Premium Service', 'price' => '100'],
            ],
            'areas' => ['Area 1', 'Area 2', 'Area 3'],
            'operating_hours' => [
                'monday' => ['open' => '09:00', 'close' => '17:00'],
                'tuesday' => ['open' => '09:00', 'close' => '17:00'],
                'wednesday' => ['open' => '09:00', 'close' => '17:00'],
                'thursday' => ['open' => '09:00', 'close' => '17:00'],
                'friday' => ['open' => '09:00', 'close' => '17:00'],
                'saturday' => ['closed' => true],
                'sunday' => ['closed' => true],
            ],
            'booking_method' => 'Call us or WhatsApp to book',
            'profile_data' => [],
            'is_onboarded' => true,
            'llm_enabled' => true,
        ];
    }
}
