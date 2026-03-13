<?php

use App\Models\GoogleCalendarConnection;
use App\Models\RoutineBlock;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;

test('google calendar connection belongs to user', function () {
    $user = User::factory()->create(['google_id' => 'google-123']);
    $connection = GoogleCalendarConnection::factory()->create(['user_id' => $user->id]);

    expect($connection->user->id)->toBe($user->id);
});

test('user has one google calendar connection', function () {
    $user = User::factory()->create(['google_id' => 'google-123']);
    $connection = GoogleCalendarConnection::factory()->create(['user_id' => $user->id]);

    expect($user->googleCalendarConnection->id)->toBe($connection->id);
});

test('routine block belongs to google calendar connection', function () {
    $user = User::factory()->create(['google_id' => 'google-123']);
    $connection = GoogleCalendarConnection::factory()->create(['user_id' => $user->id]);
    $block = RoutineBlock::factory()->create([
        'user_id' => $user->id,
        'type' => 'google_calendar',
        'google_calendar_connection_id' => $connection->id,
    ]);

    expect($block->googleCalendarConnection->id)->toBe($connection->id);
});

test('deleting connection nullifies routine block FK', function () {
    $user = User::factory()->create(['google_id' => 'google-123']);
    $connection = GoogleCalendarConnection::factory()->create(['user_id' => $user->id]);
    $block = RoutineBlock::factory()->create([
        'user_id' => $user->id,
        'type' => 'google_calendar',
        'google_calendar_connection_id' => $connection->id,
    ]);

    $connection->delete();

    expect($block->fresh()->google_calendar_connection_id)->toBeNull();
});

test('tokens are hidden from serialization', function () {
    $connection = GoogleCalendarConnection::factory()->create();
    $array = $connection->toArray();

    expect($array)->not->toHaveKey('access_token');
    expect($array)->not->toHaveKey('refresh_token');
});

test('isTokenExpired returns true when token is expired', function () {
    $connection = GoogleCalendarConnection::factory()->create([
        'token_expires_at' => now()->subMinute(),
    ]);

    expect($connection->isTokenExpired())->toBeTrue();
});

test('isTokenExpired returns false when token is valid', function () {
    $connection = GoogleCalendarConnection::factory()->create([
        'token_expires_at' => now()->addHour(),
    ]);

    expect($connection->isTokenExpired())->toBeFalse();
});

test('getCredentialAttributes returns access_token and refresh_token', function () {
    $connection = new GoogleCalendarConnection;

    expect($connection->getCredentialAttributes())->toBe(['access_token', 'refresh_token']);
});

// OAuth flow tests

test('guest cannot access google calendar connect', function () {
    $this->get(route('morning-hub.google-calendar.connect'))
        ->assertRedirect(route('login'));
});

test('user without google account cannot connect calendar', function () {
    $user = User::factory()->create(['google_id' => null]);

    $this->actingAs($user)
        ->get(route('morning-hub.google-calendar.connect'))
        ->assertRedirect()
        ->assertSessionHas('error');
});

test('user with google account is redirected to google oauth', function () {
    $user = User::factory()->create(['google_id' => 'google-123']);

    Socialite::shouldReceive('driver->scopes->with->redirectUrl->redirect')
        ->once()
        ->andReturn(redirect('https://accounts.google.com/o/oauth2/auth'));

    $this->actingAs($user)
        ->get(route('morning-hub.google-calendar.connect'))
        ->assertRedirect();
});

test('callback creates connection when google_id matches', function () {
    $user = User::factory()->create(['google_id' => 'google-123']);
    $socialiteUser = Mockery::mock(\Laravel\Socialite\Two\User::class);
    $socialiteUser->id = 'google-123';
    $socialiteUser->token = 'access-token-123';
    $socialiteUser->refreshToken = 'refresh-token-123';
    $socialiteUser->expiresIn = 3600;
    $socialiteUser->email = 'user@example.com';

    Socialite::shouldReceive('driver->redirectUrl->user')
        ->once()
        ->andReturn($socialiteUser);

    $this->actingAs($user)
        ->get(route('morning-hub.google-calendar.callback'))
        ->assertRedirect(route('morning-hub.google-calendar.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('google_calendar_connections', [
        'user_id' => $user->id,
        'google_id' => 'google-123',
        'name' => 'user@example.com',
    ]);
});

test('callback rejects mismatched google_id', function () {
    $user = User::factory()->create(['google_id' => 'google-123']);
    $socialiteUser = Mockery::mock(\Laravel\Socialite\Two\User::class);
    $socialiteUser->id = 'different-google-id';

    Socialite::shouldReceive('driver->redirectUrl->user')
        ->once()
        ->andReturn($socialiteUser);

    $this->actingAs($user)
        ->get(route('morning-hub.google-calendar.callback'))
        ->assertRedirect(route('morning-hub.google-calendar.index'))
        ->assertSessionHas('error');

    $this->assertDatabaseMissing('google_calendar_connections', [
        'user_id' => $user->id,
    ]);
});

test('disconnect deletes connection', function () {
    $user = User::factory()->create(['google_id' => 'google-123']);
    $connection = GoogleCalendarConnection::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->delete(route('morning-hub.google-calendar.disconnect'))
        ->assertRedirect(route('morning-hub.google-calendar.index'));

    $this->assertDatabaseMissing('google_calendar_connections', ['id' => $connection->id]);
});

test('disconnect when no connection redirects gracefully', function () {
    $user = User::factory()->create(['google_id' => 'google-123']);

    $this->actingAs($user)
        ->delete(route('morning-hub.google-calendar.disconnect'))
        ->assertRedirect(route('morning-hub.google-calendar.index'));
});

test('unlinking google account cascades to delete calendar connection', function () {
    $user = User::factory()->create([
        'google_id' => 'google-123',
        'password' => bcrypt('password'),
    ]);
    GoogleCalendarConnection::factory()->create(['user_id' => $user->id]);

    $authService = app(\App\Services\GoogleAuthService::class);
    $authService->unlinkAccount($user);

    $this->assertDatabaseMissing('google_calendar_connections', ['user_id' => $user->id]);
    expect($user->fresh()->google_id)->toBeNull();
});

// Config + API controller tests

test('index page shows connection status', function () {
    $user = User::factory()->create(['google_id' => 'google-123']);

    $this->actingAs($user)
        ->get(route('morning-hub.google-calendar.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('morning-hub/GoogleCalendar')
            ->has('connection', null)
        );
});

test('index page shows existing connection', function () {
    $user = User::factory()->create(['google_id' => 'google-123']);
    GoogleCalendarConnection::factory()->create(['user_id' => $user->id, 'name' => 'user@test.com']);

    $this->actingAs($user)
        ->get(route('morning-hub.google-calendar.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('morning-hub/GoogleCalendar')
            ->where('connection.name', 'user@test.com')
        );
});

test('update saves selected calendar_ids', function () {
    $user = User::factory()->create(['google_id' => 'google-123']);
    $connection = GoogleCalendarConnection::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->put(route('morning-hub.google-calendar.update'), [
            'calendar_ids' => ['primary', 'work@example.com'],
        ])
        ->assertRedirect(route('morning-hub.google-calendar.index'));

    expect($connection->fresh()->calendar_ids)->toBe(['primary', 'work@example.com']);
});

test('update rejects invalid calendar_ids', function () {
    $user = User::factory()->create(['google_id' => 'google-123']);
    GoogleCalendarConnection::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->put(route('morning-hub.google-calendar.update'), [
            'calendar_ids' => 'not-an-array',
        ])
        ->assertSessionHasErrors('calendar_ids');
});

test('test endpoint returns success for valid connection', function () {
    $user = User::factory()->create(['google_id' => 'google-123']);
    GoogleCalendarConnection::factory()->create(['user_id' => $user->id]);

    Http::fake([
        'www.googleapis.com/calendar/v3/users/me/calendarList*' => Http::response(['items' => []]),
    ]);

    $this->actingAs($user)
        ->postJson(route('morning-hub.google-calendar.test'))
        ->assertOk()
        ->assertJson(['success' => true]);
});

test('calendars endpoint returns calendar list', function () {
    $user = User::factory()->create(['google_id' => 'google-123']);
    GoogleCalendarConnection::factory()->create(['user_id' => $user->id]);

    Http::fake([
        'www.googleapis.com/calendar/v3/users/me/calendarList*' => Http::response([
            'items' => [
                ['id' => 'primary', 'summary' => 'My Cal', 'backgroundColor' => '#4285f4'],
            ],
        ]),
    ]);

    $this->actingAs($user)
        ->getJson(route('morning-hub.google-calendar.calendars'))
        ->assertOk()
        ->assertJsonCount(1, 'calendars')
        ->assertJsonPath('calendars.0.id', 'primary');
});

test('calendars endpoint returns 404 when no connection', function () {
    $user = User::factory()->create(['google_id' => 'google-123']);

    $this->actingAs($user)
        ->getJson(route('morning-hub.google-calendar.calendars'))
        ->assertNotFound();
});
