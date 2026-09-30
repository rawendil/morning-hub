<?php

use App\Models\User;

test('user can register with valid data', function () {
    $this->postJson('/api/auth/register', [
        'name' => 'Jan Kowalski',
        'email' => 'jan@example.com',
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
        'terms' => true,
    ])->assertCreated()->assertJsonStructure(['token', 'user' => ['id', 'name', 'email']]);

    expect(User::where('email', 'jan@example.com')->exists())->toBeTrue();
});

test('register returns 422 on duplicate email', function () {
    User::factory()->create(['email' => 'existing@example.com']);

    $this->postJson('/api/auth/register', [
        'name' => 'Test',
        'email' => 'existing@example.com',
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
        'terms' => true,
    ])->assertUnprocessable();
});

test('register returns 422 on missing fields', function () {
    $this->postJson('/api/auth/register', [])->assertUnprocessable();
});

test('registration stores the moment the terms were accepted', function () {
    $this->freezeSecond();

    $this->postJson('/api/auth/register', [
        'name' => 'Jan Kowalski',
        'email' => 'jan@example.com',
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
        'terms' => true,
    ])->assertCreated();

    expect(User::where('email', 'jan@example.com')->first()->terms_accepted_at->equalTo(now()))->toBeTrue();
});

test('registration is rejected when the terms are not accepted', function (array $terms) {
    config(['app.locale' => 'pl']);

    $this->postJson('/api/auth/register', [
        'name' => 'Jan Kowalski',
        'email' => 'jan@example.com',
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
        ...$terms,
    ])->assertUnprocessable()->assertJsonValidationErrors([
        'terms' => 'Musisz potwierdzić, że masz ukończone 18 lat, i zaakceptować Regulamin oraz Politykę prywatności.',
    ]);

    expect(User::where('email', 'jan@example.com')->exists())->toBeFalse();
})->with([
    'missing' => [[]],
    'false' => [['terms' => false]],
    'zero string' => [['terms' => '0']],
]);

test('the terms error is translated for english speakers', function () {
    config(['app.locale' => 'en']);

    $this->postJson('/api/auth/register', [
        'name' => 'Jan Kowalski',
        'email' => 'jan@example.com',
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
    ])->assertUnprocessable()->assertJsonValidationErrors([
        'terms' => 'You must confirm that you are at least 18 years old and accept the Terms of service and Privacy policy.',
    ]);
});

test('terms acceptance cannot be mass assigned', function () {
    $user = User::create([
        'name' => 'Jan',
        'email' => 'mass@example.com',
        'password' => 'Password1!',
        'terms_accepted_at' => now(),
    ]);

    expect($user->fresh()->terms_accepted_at)->toBeNull();
});
