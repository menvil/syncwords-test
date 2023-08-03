<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{

    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Event::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start_date = fake()->dateTimeBetween('+0 days', '+1 month');
        $start_date_clone = clone $start_date;
        $end_date = fake()->dateTimeBetween($start_date, $start_date_clone->modify('+'.rand(1, 11).' hours'));

        return [
            'event_title' => fake()->sentence(3),
            'event_start_date' => $start_date,
            'event_end_date' => $end_date,
            'organization_id' => function (array $attributes) {
                return User::findOrFail($attributes['organization_id'])->id;
            }
        ];
    }
}
