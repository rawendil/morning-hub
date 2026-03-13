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
            'google_id' => 'google-'.fake()->randomNumber(6),
            'name' => fake()->unique()->safeEmail(),
            'access_token' => 'ya29.'.fake()->sha1(),
            'refresh_token' => '1//'.fake()->sha1(),
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

    /** @param string[] $calendarIds */
    public function withCalendars(array $calendarIds = ['primary']): static
    {
        return $this->state(fn () => [
            'calendar_ids' => $calendarIds,
        ]);
    }
}
