<?php

use App\Models\User;

test('locale uses app default when no cookie is set', function () {
    config(['app.locale' => 'pl']);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('locale', 'pl')
        );
});

test('locale is set to en from cookie', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withUnencryptedCookie('locale', 'en')
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('locale', 'en')
        );
});

test('locale is set to pl from cookie', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withUnencryptedCookie('locale', 'pl')
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('locale', 'pl')
        );
});

test('invalid locale falls back to app default', function () {
    config(['app.locale' => 'pl']);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withUnencryptedCookie('locale', 'de')
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('locale', 'pl')
        );
});

test('translations prop is shared via inertia', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('translations')
        );
});
