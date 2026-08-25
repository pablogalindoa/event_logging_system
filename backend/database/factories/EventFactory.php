<?php

namespace Database\Factories;

use App\Enums\EventLevel;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventFactory extends Factory
{
    public function definition(): array
    {
        return [
            'level' => fake()->randomElement(EventLevel::cases()),
            'message' => fake()->sentence(),
            'source' => fake()->optional()->slug(2),
            'context' => ['trace_id' => fake()->uuid()],
            'occurred_at' => fake()->dateTimeBetween('-1 day'),
        ];
    }
}
