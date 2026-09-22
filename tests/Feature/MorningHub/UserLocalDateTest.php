<?php

use App\Models\User;
use Illuminate\Support\Carbon;

afterEach(function () {
    Carbon::setTestNow();
});

test('local date follows the user timezone across the UTC midnight boundary', function () {
    Carbon::setTestNow(Carbon::parse('2026-09-22 23:30:00', 'UTC'));

    $user = User::factory()->create(['timezone' => 'Europe/Warsaw']);

    expect($user->localDate()->toDateString())->toBe('2026-09-23');
});

test('local date falls back to UTC when the user has no timezone', function () {
    Carbon::setTestNow(Carbon::parse('2026-09-22 23:30:00', 'UTC'));

    $user = User::factory()->create(['timezone' => null]);

    expect($user->localDate()->toDateString())->toBe('2026-09-22');
});

test('local date falls back to UTC when the stored timezone is invalid', function () {
    Carbon::setTestNow(Carbon::parse('2026-09-22 23:30:00', 'UTC'));

    $user = User::factory()->create(['timezone' => 'Not/AZone']);

    expect($user->localDate()->toDateString())->toBe('2026-09-22');
});
