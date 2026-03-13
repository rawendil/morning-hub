<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GoogleCalendarConnection> */
class GoogleCalendarConnectionFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'google_id' => $this->faker->numerify('google-######'),
            'name' => $this->faker->safeEmail(),
            'access_token' => 'ya29.'.$this->faker->sha256(),
            'refresh_token' => '1//'.$this->faker->sha256(),
            'token_expires_at' => now()->addHour(),
            'calendar_ids' => null,
        ];
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'token_expires_at' => now()->subMinute(),
        ]);
    }

    public function withCalendars(array $calendarIds = ['primary']): static
    {
        return $this->state(fn () => [
            'calendar_ids' => $calendarIds,
        ]);
    }
}
