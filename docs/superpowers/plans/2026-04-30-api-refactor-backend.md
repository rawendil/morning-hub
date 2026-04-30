# API Refactor — Etap 1: Backend Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Zastąpić wszystkie kontrolery zwracające `Inertia::render()` kontrolerami API zwracającymi `JsonResponse`, dodać Sanctum token auth i przenieść całą logikę routingu do `routes/api.php`.

**Architecture:** Nowe kontrolery w `app/Http/Controllers/Api/` delegują logikę do istniejących serwisów i Fortify Actions bez ich modyfikacji. Auth opiera się na Sanctum Personal Access Tokens + Laravel Cache (2FA challenge) + Socialite stateless (Google). Istniejące testy przepisywane z `assertInertia()` na asercje JSON.

**Tech Stack:** Laravel 12, Laravel Sanctum (PAT), Laravel Fortify (TwoFactorAuthenticationProvider, Actions), Laravel Socialite (stateless), Pest 4

---

## File Map

### Nowe pliki
- `app/Http/Controllers/Api/Auth/LoginController.php`
- `app/Http/Controllers/Api/Auth/TwoFactorController.php`
- `app/Http/Controllers/Api/Auth/LogoutController.php`
- `app/Http/Controllers/Api/Auth/RegisterController.php`
- `app/Http/Controllers/Api/Auth/ForgotPasswordController.php`
- `app/Http/Controllers/Api/Auth/ResetPasswordController.php`
- `app/Http/Controllers/Api/Auth/GoogleAuthController.php`
- `app/Http/Controllers/Api/UserController.php`
- `app/Http/Controllers/Api/DashboardController.php`
- `app/Http/Controllers/Api/TodaysTasksController.php`
- `app/Http/Controllers/Api/RoutineBlockController.php`
- `app/Http/Controllers/Api/ClickUpConnectionController.php`
- `app/Http/Controllers/Api/ClickUpApiController.php`
- `app/Http/Controllers/Api/GoogleCalendarConnectionController.php`
- `app/Http/Controllers/Api/GoogleCalendarApiController.php`
- `app/Http/Controllers/Api/TodaysTasksConfigController.php`
- `app/Http/Controllers/Api/Settings/ProfileController.php`
- `app/Http/Controllers/Api/Settings/PasswordController.php`
- `app/Http/Controllers/Api/Settings/TwoFactorAuthenticationController.php`
- `app/Http/Controllers/Api/Settings/AppearanceController.php`
- `tests/Feature/Api/Auth/LoginTest.php`
- `tests/Feature/Api/Auth/TwoFactorTest.php`
- `tests/Feature/Api/Auth/LogoutTest.php`
- `tests/Feature/Api/Auth/RegisterTest.php`
- `tests/Feature/Api/Auth/PasswordTest.php`
- `tests/Feature/Api/Auth/GoogleAuthTest.php`
- `tests/Feature/Api/UserTest.php`
- `tests/Feature/Api/DashboardTest.php`
- `tests/Feature/Api/RoutineBlockTest.php`
- `tests/Feature/Api/ClickUpConnectionTest.php`
- `tests/Feature/Api/GoogleCalendarConnectionTest.php`
- `tests/Feature/Api/TodaysTasksConfigTest.php`
- `tests/Feature/Api/Settings/ProfileTest.php`
- `tests/Feature/Api/Settings/PasswordTest.php`
- `tests/Feature/Api/Settings/TwoFactorTest.php`

### Modyfikowane pliki
- `app/Models/User.php` — dodanie `HasApiTokens`
- `app/Services/GoogleAuthService.php` — nowa metoda `handleApiLogin(string $accessToken)`
- `bootstrap/app.php` — rejestracja `routes/api.php`, usunięcie Inertia middleware
- `routes/web.php` — uproszczenie do catch-all SPA + Google Calendar OAuth
- `routes/api.php` — pełna konsolidacja wszystkich endpointów

### Usuwane pliki
- `app/Http/Middleware/HandleInertiaRequests.php`
- `routes/morning-hub.php`
- `routes/settings.php`
- Stare kontrolery Inertia (po przepisaniu testów i weryfikacji)

---

## Task 1: Instalacja Sanctum + HasApiTokens

**Files:**
- Modify: `app/Models/User.php`
- Modify: `config/sanctum.php` (tworzony przez publish)

- [ ] **Krok 1: Zainstaluj Sanctum**

```bash
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider" --no-interaction
php artisan migrate --no-interaction
```

Oczekiwany output: migracja tworzy tabelę `personal_access_tokens`.

- [ ] **Krok 2: Dodaj `HasApiTokens` do User**

Otwórz `app/Models/User.php` i dodaj trait:

```php
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, HasApiTokens, Notifiable, TwoFactorAuthenticatable;
```

- [ ] **Krok 3: Zweryfikuj że Sanctum działa**

```bash
php artisan tinker --no-interaction --execute="echo App\Models\User::factory()->create()->createToken('test')->plainTextToken;"
```

Oczekiwany output: ciąg znaków `1|...` (Sanctum token).

- [ ] **Krok 4: Commit**

```bash
git add app/Models/User.php config/sanctum.php database/migrations/
git commit -m "feat: install sanctum and add HasApiTokens to User"
```

---

## Task 2: LoginController (bez 2FA)

**Files:**
- Create: `app/Http/Controllers/Api/Auth/LoginController.php`
- Create: `tests/Feature/Api/Auth/LoginTest.php`

- [ ] **Krok 1: Napisz testy**

```bash
php artisan make:test --pest Api/Auth/LoginTest --no-interaction
```

Zastąp zawartość `tests/Feature/Api/Auth/LoginTest.php`:

```php
<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('user can login with valid credentials', function () {
    $user = User::factory()->create(['password' => Hash::make('password')]);

    $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertOk()->assertJsonStructure(['token', 'user' => ['id', 'name', 'email']]);
});

test('login returns 422 on wrong password', function () {
    $user = User::factory()->create(['password' => Hash::make('password')]);

    $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'wrong',
    ])->assertUnprocessable();
});

test('login returns 422 on missing fields', function () {
    $this->postJson('/api/auth/login', [])->assertUnprocessable();
});
```

- [ ] **Krok 2: Uruchom testy — muszą failować**

```bash
php artisan test --compact --filter=LoginTest
```

Oczekiwany output: FAIL (route nie istnieje).

- [ ] **Krok 3: Utwórz kontroler**

```bash
php artisan make:class app/Http/Controllers/Api/Auth/LoginController --no-interaction
```

Zastąp zawartość `app/Http/Controllers/Api/Auth/LoginController.php`:

```php
<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = \App\Models\User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password ?? '')) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        if ($user->hasEnabledTwoFactorAuthentication()) {
            $tempToken = \Illuminate\Support\Str::random(40);
            \Illuminate\Support\Facades\Cache::put("2fa_challenge:{$tempToken}", $user->id, 300);

            return response()->json([
                'requires_2fa' => true,
                'temp_token' => $tempToken,
            ]);
        }

        $token = $user->createToken('web')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user->only(['id', 'name', 'email', 'google_avatar']),
        ]);
    }
}
```

- [ ] **Krok 4: Dodaj route tymczasowo do `routes/api.php`**

Jeśli `routes/api.php` nie istnieje, utwórz go:

```php
<?php

use App\Http\Controllers\Api\Auth\LoginController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', LoginController::class)->name('api.auth.login');
```

Zarejestruj `routes/api.php` w `bootstrap/app.php` — dodaj do `withRouting()`:

```php
->withRouting(
    web: __DIR__.'/../routes/web.php',
    api: __DIR__.'/../routes/api.php',
    commands: __DIR__.'/../routes/console.php',
    health: '/up',
    then: function () {
        Route::middleware('web')
            ->group(base_path('routes/morning-hub.php'));
    },
)
```

- [ ] **Krok 5: Uruchom testy — muszą przechodzić**

```bash
php artisan test --compact --filter=LoginTest
```

Oczekiwany output: 3 testy PASS.

- [ ] **Krok 6: Commit**

```bash
git add app/Http/Controllers/Api/Auth/LoginController.php tests/Feature/Api/Auth/LoginTest.php routes/api.php bootstrap/app.php
git commit -m "feat: add api login endpoint with sanctum token"
```

---

## Task 3: LoginController (przypadek 2FA) + TwoFactorController

**Files:**
- Modify: `tests/Feature/Api/Auth/LoginTest.php`
- Create: `app/Http/Controllers/Api/Auth/TwoFactorController.php`
- Create: `tests/Feature/Api/Auth/TwoFactorTest.php`

- [ ] **Krok 1: Dodaj test 2FA do LoginTest**

Dopisz do `tests/Feature/Api/Auth/LoginTest.php`:

```php
test('login returns temp_token when user has 2fa enabled', function () {
    $user = User::factory()->create(['password' => Hash::make('password')]);
    $user->forceFill([
        'two_factor_secret' => encrypt('secret'),
        'two_factor_confirmed_at' => now(),
    ])->save();

    $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertOk()->assertJson(['requires_2fa' => true])->assertJsonStructure(['temp_token']);
});

test('login does not return token directly when user has 2fa enabled', function () {
    $user = User::factory()->create(['password' => Hash::make('password')]);
    $user->forceFill([
        'two_factor_secret' => encrypt('secret'),
        'two_factor_confirmed_at' => now(),
    ])->save();

    $response = $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertJsonMissing(['token']);
});
```

- [ ] **Krok 2: Napisz testy TwoFactorController**

```bash
php artisan make:test --pest Api/Auth/TwoFactorTest --no-interaction
```

Zastąp zawartość `tests/Feature/Api/Auth/TwoFactorTest.php`:

```php
<?php

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;

test('returns token with valid 2fa code', function () {
    $user = User::factory()->create();
    $user->forceFill([
        'two_factor_secret' => encrypt('secret'),
        'two_factor_confirmed_at' => now(),
    ])->save();

    $tempToken = Str::random(40);
    Cache::put("2fa_challenge:{$tempToken}", $user->id, 300);

    $this->mock(TwoFactorAuthenticationProvider::class, function ($mock) {
        $mock->shouldReceive('verify')->once()->andReturn(true);
    });

    $this->postJson('/api/auth/two-factor', [
        'temp_token' => $tempToken,
        'code' => '123456',
    ])->assertOk()->assertJsonStructure(['token', 'user']);
});

test('returns 422 with invalid 2fa code', function () {
    $user = User::factory()->create();
    $user->forceFill([
        'two_factor_secret' => encrypt('secret'),
        'two_factor_confirmed_at' => now(),
    ])->save();

    $tempToken = Str::random(40);
    Cache::put("2fa_challenge:{$tempToken}", $user->id, 300);

    $this->mock(TwoFactorAuthenticationProvider::class, function ($mock) {
        $mock->shouldReceive('verify')->once()->andReturn(false);
    });

    $this->postJson('/api/auth/two-factor', [
        'temp_token' => $tempToken,
        'code' => '000000',
    ])->assertUnprocessable();
});

test('returns 422 when temp_token expired or not found', function () {
    $this->postJson('/api/auth/two-factor', [
        'temp_token' => 'nonexistent-token',
        'code' => '123456',
    ])->assertUnprocessable();
});

test('temp_token is deleted after successful verification', function () {
    $user = User::factory()->create();
    $user->forceFill([
        'two_factor_secret' => encrypt('secret'),
        'two_factor_confirmed_at' => now(),
    ])->save();

    $tempToken = Str::random(40);
    Cache::put("2fa_challenge:{$tempToken}", $user->id, 300);

    $this->mock(TwoFactorAuthenticationProvider::class, function ($mock) {
        $mock->shouldReceive('verify')->once()->andReturn(true);
    });

    $this->postJson('/api/auth/two-factor', [
        'temp_token' => $tempToken,
        'code' => '123456',
    ]);

    expect(Cache::has("2fa_challenge:{$tempToken}"))->toBeFalse();
});
```

- [ ] **Krok 3: Uruchom testy — muszą failować**

```bash
php artisan test --compact --filter="TwoFactorTest|LoginTest"
```

- [ ] **Krok 4: Utwórz TwoFactorController**

```bash
php artisan make:class app/Http/Controllers/Api/Auth/TwoFactorController --no-interaction
```

Zawartość `app/Http/Controllers/Api/Auth/TwoFactorController.php`:

```php
<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;

class TwoFactorController extends Controller
{
    public function __construct(
        private readonly TwoFactorAuthenticationProvider $provider,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'temp_token' => ['required', 'string'],
            'code' => ['required', 'string'],
        ]);

        $cacheKey = "2fa_challenge:{$request->temp_token}";
        $userId = Cache::get($cacheKey);

        if (! $userId) {
            throw ValidationException::withMessages([
                'temp_token' => [__('The session has expired. Please log in again.')],
            ]);
        }

        $user = User::findOrFail($userId);

        if (! $this->provider->verify(decrypt($user->two_factor_secret), $request->code)) {
            throw ValidationException::withMessages([
                'code' => [__('The provided two factor authentication code was invalid.')],
            ]);
        }

        Cache::forget($cacheKey);

        $token = $user->createToken('web')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user->only(['id', 'name', 'email', 'google_avatar']),
        ]);
    }
}
```

- [ ] **Krok 5: Dodaj route do `routes/api.php`**

```php
use App\Http\Controllers\Api\Auth\TwoFactorController;

Route::post('/auth/two-factor', TwoFactorController::class)->name('api.auth.two-factor');
```

- [ ] **Krok 6: Uruchom testy — muszą przechodzić**

```bash
php artisan test --compact --filter="TwoFactorTest|LoginTest"
```

Oczekiwany output: 7 testów PASS.

- [ ] **Krok 7: Commit**

```bash
git add app/Http/Controllers/Api/Auth/TwoFactorController.php tests/Feature/Api/Auth/TwoFactorTest.php tests/Feature/Api/Auth/LoginTest.php routes/api.php
git commit -m "feat: add api 2fa challenge endpoint"
```

---

## Task 4: LogoutController + RegisterController

**Files:**
- Create: `app/Http/Controllers/Api/Auth/LogoutController.php`
- Create: `app/Http/Controllers/Api/Auth/RegisterController.php`
- Create: `tests/Feature/Api/Auth/LogoutTest.php`
- Create: `tests/Feature/Api/Auth/RegisterTest.php`

- [ ] **Krok 1: Napisz testy**

```bash
php artisan make:test --pest Api/Auth/LogoutTest --no-interaction
php artisan make:test --pest Api/Auth/RegisterTest --no-interaction
```

Zawartość `tests/Feature/Api/Auth/LogoutTest.php`:

```php
<?php

use App\Models\User;

test('authenticated user can logout', function () {
    $user = User::factory()->create();
    $token = $user->createToken('web');

    $this->withToken($token->plainTextToken)
        ->postJson('/api/auth/logout')
        ->assertNoContent();
});

test('token is deleted after logout', function () {
    $user = User::factory()->create();
    $token = $user->createToken('web');

    $this->withToken($token->plainTextToken)
        ->postJson('/api/auth/logout');

    expect($user->tokens()->count())->toBe(0);
});

test('unauthenticated user cannot logout', function () {
    $this->postJson('/api/auth/logout')->assertUnauthorized();
});
```

Zawartość `tests/Feature/Api/Auth/RegisterTest.php`:

```php
<?php

use App\Models\User;

test('user can register with valid data', function () {
    $this->postJson('/api/auth/register', [
        'name' => 'Jan Kowalski',
        'email' => 'jan@example.com',
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
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
    ])->assertUnprocessable();
});

test('register returns 422 on missing fields', function () {
    $this->postJson('/api/auth/register', [])->assertUnprocessable();
});
```

- [ ] **Krok 2: Uruchom testy — muszą failować**

```bash
php artisan test --compact --filter="LogoutTest|RegisterTest"
```

- [ ] **Krok 3: Utwórz LogoutController**

```bash
php artisan make:class app/Http/Controllers/Api/Auth/LogoutController --no-interaction
```

Zawartość `app/Http/Controllers/Api/Auth/LogoutController.php`:

```php
<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class LogoutController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }
}
```

- [ ] **Krok 4: Utwórz RegisterController**

`RegisterController` deleguje do `app/Actions/Fortify/CreateNewUser`:

```bash
php artisan make:class app/Http/Controllers/Api/Auth/RegisterController --no-interaction
```

Zawartość `app/Http/Controllers/Api/Auth/RegisterController.php`:

```php
<?php

namespace App\Http\Controllers\Api\Auth;

use App\Actions\Fortify\CreateNewUser;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RegisterController extends Controller
{
    public function __construct(
        private readonly CreateNewUser $createNewUser,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $user = $this->createNewUser->create($request->all());

        $token = $user->createToken('web')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user->only(['id', 'name', 'email']),
        ], 201);
    }
}
```

- [ ] **Krok 5: Dodaj routes do `routes/api.php`**

```php
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\Auth\RegisterController;

Route::post('/auth/register', RegisterController::class)->name('api.auth.register');
Route::middleware('auth:sanctum')->post('/auth/logout', LogoutController::class)->name('api.auth.logout');
```

- [ ] **Krok 6: Uruchom testy — muszą przechodzić**

```bash
php artisan test --compact --filter="LogoutTest|RegisterTest"
```

Oczekiwany output: 6 testów PASS.

- [ ] **Krok 7: Commit**

```bash
git add app/Http/Controllers/Api/Auth/LogoutController.php app/Http/Controllers/Api/Auth/RegisterController.php tests/Feature/Api/Auth/LogoutTest.php tests/Feature/Api/Auth/RegisterTest.php routes/api.php
git commit -m "feat: add api logout and register endpoints"
```

---

## Task 5: Forgot/Reset Password

**Files:**
- Create: `app/Http/Controllers/Api/Auth/ForgotPasswordController.php`
- Create: `app/Http/Controllers/Api/Auth/ResetPasswordController.php`
- Create: `tests/Feature/Api/Auth/PasswordTest.php`

- [ ] **Krok 1: Napisz testy**

```bash
php artisan make:test --pest Api/Auth/PasswordTest --no-interaction
```

Zawartość `tests/Feature/Api/Auth/PasswordTest.php`:

```php
<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

test('forgot password sends reset email', function () {
    Notification::fake();
    $user = User::factory()->create();

    $this->postJson('/api/auth/forgot-password', ['email' => $user->email])
        ->assertNoContent();

    Notification::assertSentTo($user, ResetPassword::class);
});

test('forgot password returns 204 even for unknown email', function () {
    $this->postJson('/api/auth/forgot-password', ['email' => 'unknown@example.com'])
        ->assertNoContent();
});

test('reset password with valid token updates password', function () {
    $user = User::factory()->create();
    $token = Password::createToken($user);

    $this->postJson('/api/auth/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'NewPassword1!',
        'password_confirmation' => 'NewPassword1!',
    ])->assertNoContent();
});

test('reset password with invalid token returns 422', function () {
    $user = User::factory()->create();

    $this->postJson('/api/auth/reset-password', [
        'token' => 'invalid-token',
        'email' => $user->email,
        'password' => 'NewPassword1!',
        'password_confirmation' => 'NewPassword1!',
    ])->assertUnprocessable();
});
```

- [ ] **Krok 2: Uruchom testy — muszą failować**

```bash
php artisan test --compact --filter=PasswordTest
```

- [ ] **Krok 3: Utwórz ForgotPasswordController**

```bash
php artisan make:class app/Http/Controllers/Api/Auth/ForgotPasswordController --no-interaction
```

Zawartość `app/Http/Controllers/Api/Auth/ForgotPasswordController.php`:

```php
<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $request->validate(['email' => ['required', 'email']]);

        Password::sendResetLink($request->only('email'));

        return response()->noContent();
    }
}
```

- [ ] **Krok 4: Utwórz ResetPasswordController**

`ResetPasswordController` deleguje do `app/Actions/Fortify/ResetUserPassword`:

```bash
php artisan make:class app/Http/Controllers/Api/Auth/ResetPasswordController --no-interaction
```

Zawartość `app/Http/Controllers/Api/Auth/ResetPasswordController.php`:

```php
<?php

namespace App\Http\Controllers\Api\Auth;

use App\Actions\Fortify\ResetUserPassword;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class ResetPasswordController extends Controller
{
    public function __construct(
        private readonly ResetUserPassword $resetUserPassword,
    ) {}

    public function __invoke(Request $request): Response
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'confirmed'],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $this->resetUserPassword->reset($user, ['password' => $password, 'password_confirmation' => $password]);
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'token' => [__($status)],
            ]);
        }

        return response()->noContent();
    }
}
```

- [ ] **Krok 5: Dodaj routes**

```php
use App\Http\Controllers\Api\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\Auth\ResetPasswordController;

Route::post('/auth/forgot-password', ForgotPasswordController::class)->name('api.auth.forgot-password');
Route::post('/auth/reset-password', ResetPasswordController::class)->name('api.auth.reset-password');
```

- [ ] **Krok 6: Uruchom testy — muszą przechodzić**

```bash
php artisan test --compact --filter=PasswordTest
```

Oczekiwany output: 4 testy PASS.

- [ ] **Krok 7: Commit**

```bash
git add app/Http/Controllers/Api/Auth/ForgotPasswordController.php app/Http/Controllers/Api/Auth/ResetPasswordController.php tests/Feature/Api/Auth/PasswordTest.php routes/api.php
git commit -m "feat: add api forgot/reset password endpoints"
```

---

## Task 6: GoogleAuthController (API) + GoogleAuthService update

**Files:**
- Modify: `app/Services/GoogleAuthService.php`
- Create: `app/Http/Controllers/Api/Auth/GoogleAuthController.php`
- Create: `tests/Feature/Api/Auth/GoogleAuthTest.php`

- [ ] **Krok 1: Napisz testy**

```bash
php artisan make:test --pest Api/Auth/GoogleAuthTest --no-interaction
```

Zawartość `tests/Feature/Api/Auth/GoogleAuthTest.php`:

```php
<?php

use App\Models\User;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;

test('existing user can login with valid google access token', function () {
    $user = User::factory()->create(['google_id' => 'google-123', 'email' => 'user@example.com']);

    $socialiteUser = Mockery::mock(SocialiteUser::class);
    $socialiteUser->shouldReceive('getId')->andReturn('google-123');
    $socialiteUser->shouldReceive('getEmail')->andReturn('user@example.com');
    $socialiteUser->shouldReceive('getAvatar')->andReturn(null);

    Socialite::shouldReceive('driver->stateless->userFromToken')
        ->once()
        ->andReturn($socialiteUser);

    $this->postJson('/api/auth/google', ['access_token' => 'valid-token'])
        ->assertOk()
        ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email']]);
});

test('new user is registered via google', function () {
    $socialiteUser = Mockery::mock(SocialiteUser::class);
    $socialiteUser->shouldReceive('getId')->andReturn('new-google-id');
    $socialiteUser->shouldReceive('getEmail')->andReturn('new@example.com');
    $socialiteUser->shouldReceive('getName')->andReturn('New User');
    $socialiteUser->shouldReceive('getAvatar')->andReturn(null);

    Socialite::shouldReceive('driver->stateless->userFromToken')
        ->once()
        ->andReturn($socialiteUser);

    $this->postJson('/api/auth/google', ['access_token' => 'valid-token'])
        ->assertOk()
        ->assertJsonStructure(['token', 'user']);

    expect(User::where('google_id', 'new-google-id')->exists())->toBeTrue();
});

test('google auth returns 422 without access_token', function () {
    $this->postJson('/api/auth/google', [])->assertUnprocessable();
});
```

- [ ] **Krok 2: Uruchom testy — muszą failować**

```bash
php artisan test --compact --filter=GoogleAuthTest
```

- [ ] **Krok 3: Dodaj metodę `handleApiLogin` do `GoogleAuthService`**

Otwórz `app/Services/GoogleAuthService.php` i dopisz nową metodę na końcu klasy (przed zamknięciem `}`):

```php
/**
 * Handle API login via Google access token (stateless, frontend-initiated OAuth).
 *
 * @return array{user: User, is_new: bool}
 */
public function handleApiLogin(string $accessToken): array
{
    $googleUser = Socialite::driver('google')->stateless()->userFromToken($accessToken);

    return DB::transaction(function () use ($googleUser) {
        $user = User::where('google_id', $googleUser->getId())->first();
        if ($user) {
            $this->updateAvatar($user, $googleUser);

            return ['user' => $user, 'is_new' => false];
        }

        $user = User::where('email', $googleUser->getEmail())->first();
        if ($user) {
            $user->forceFill([
                'google_id' => $googleUser->getId(),
                'google_avatar' => $googleUser->getAvatar(),
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();

            return ['user' => $user, 'is_new' => false];
        }

        $user = User::create([
            'name' => $googleUser->getName(),
            'email' => $googleUser->getEmail(),
            'google_id' => $googleUser->getId(),
            'google_avatar' => $googleUser->getAvatar(),
            'email_verified_at' => now(),
        ]);

        return ['user' => $user, 'is_new' => true];
    });
}
```

- [ ] **Krok 4: Utwórz GoogleAuthController (API)**

```bash
php artisan make:class app/Http/Controllers/Api/Auth/GoogleAuthController --no-interaction
```

Zawartość `app/Http/Controllers/Api/Auth/GoogleAuthController.php`:

```php
<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Services\GoogleAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GoogleAuthController extends Controller
{
    public function __construct(
        private readonly GoogleAuthService $googleAuthService,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'access_token' => ['required', 'string'],
        ]);

        $result = $this->googleAuthService->handleApiLogin($request->access_token);

        $token = $result['user']->createToken('web')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $result['user']->only(['id', 'name', 'email', 'google_avatar']),
        ]);
    }
}
```

- [ ] **Krok 5: Dodaj route**

```php
use App\Http\Controllers\Api\Auth\GoogleAuthController;

Route::post('/auth/google', GoogleAuthController::class)->name('api.auth.google');
```

- [ ] **Krok 6: Uruchom testy — muszą przechodzić**

```bash
php artisan test --compact --filter=GoogleAuthTest
```

Oczekiwany output: 3 testy PASS.

- [ ] **Krok 7: Commit**

```bash
git add app/Services/GoogleAuthService.php app/Http/Controllers/Api/Auth/GoogleAuthController.php tests/Feature/Api/Auth/GoogleAuthTest.php routes/api.php
git commit -m "feat: add api google auth endpoint (stateless socialite)"
```

---

## Task 7: UserController (GET /api/user)

**Files:**
- Create: `app/Http/Controllers/Api/UserController.php`
- Create: `tests/Feature/Api/UserTest.php`

- [ ] **Krok 1: Napisz testy**

```bash
php artisan make:test --pest Api/UserTest --no-interaction
```

Zawartość `tests/Feature/Api/UserTest.php`:

```php
<?php

use App\Models\User;

test('authenticated user can fetch their data', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/user')
        ->assertOk()
        ->assertJsonStructure(['user' => ['id', 'name', 'email'], 'locale', 'appearance']);
});

test('unauthenticated request returns 401', function () {
    $this->getJson('/api/user')->assertUnauthorized();
});
```

- [ ] **Krok 2: Uruchom testy — muszą failować**

```bash
php artisan test --compact --filter=UserTest
```

- [ ] **Krok 3: Utwórz UserController**

```bash
php artisan make:class app/Http/Controllers/Api/UserController --no-interaction
```

Zawartość `app/Http/Controllers/Api/UserController.php`:

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'user' => $user->only(['id', 'name', 'email', 'google_id', 'google_avatar', 'email_verified_at']),
            'locale' => $request->cookie('locale', config('app.locale')),
            'appearance' => $request->cookie('appearance', 'system'),
        ]);
    }
}
```

- [ ] **Krok 4: Dodaj route**

```php
use App\Http\Controllers\Api\UserController;

Route::middleware('auth:sanctum')->get('/user', UserController::class)->name('api.user');
```

- [ ] **Krok 5: Uruchom testy — muszą przechodzić**

```bash
php artisan test --compact --filter=UserTest
```

Oczekiwany output: 2 testy PASS.

- [ ] **Krok 6: Commit**

```bash
git add app/Http/Controllers/Api/UserController.php tests/Feature/Api/UserTest.php routes/api.php
git commit -m "feat: add GET /api/user endpoint"
```

---

## Task 8: RoutineBlockController (API)

**Files:**
- Create: `app/Http/Controllers/Api/RoutineBlockController.php`
- Create: `tests/Feature/Api/RoutineBlockTest.php`

- [ ] **Krok 1: Napisz testy**

```bash
php artisan make:test --pest Api/RoutineBlockTest --no-interaction
```

Zawartość `tests/Feature/Api/RoutineBlockTest.php`:

```php
<?php

use App\Models\RoutineBlock;
use App\Models\User;

test('guest cannot access routine blocks', function () {
    $this->getJson('/api/morning-hub/routine')->assertUnauthorized();
});

test('user can fetch their routine blocks', function () {
    $user = User::factory()->create();
    RoutineBlock::factory()->for($user)->create(['sort_order' => 2, 'name' => 'Second']);
    RoutineBlock::factory()->for($user)->create(['sort_order' => 1, 'name' => 'First']);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/morning-hub/routine')
        ->assertOk()
        ->assertJsonCount(2, 'blocks')
        ->assertJsonPath('blocks.0.name', 'First');
});

test('user can create a routine block', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/morning-hub/routine/blocks', [
            'type' => 'braindump',
            'name' => 'Morning Dump',
            'timer_minutes' => 10,
        ])
        ->assertCreated()
        ->assertJsonPath('block.name', 'Morning Dump');
});

test('user can update their routine block', function () {
    $user = User::factory()->create();
    $block = RoutineBlock::factory()->for($user)->create(['name' => 'Old Name']);

    $this->actingAs($user, 'sanctum')
        ->putJson("/api/morning-hub/routine/blocks/{$block->id}", ['name' => 'New Name'])
        ->assertOk()
        ->assertJsonPath('block.name', 'New Name');
});

test('user cannot update another user\'s block', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $block = RoutineBlock::factory()->for($owner)->create();

    $this->actingAs($other, 'sanctum')
        ->putJson("/api/morning-hub/routine/blocks/{$block->id}", ['name' => 'Hack'])
        ->assertForbidden();
});

test('user can delete their routine block', function () {
    $user = User::factory()->create();
    $block = RoutineBlock::factory()->for($user)->create();

    $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/morning-hub/routine/blocks/{$block->id}")
        ->assertNoContent();

    expect(RoutineBlock::find($block->id))->toBeNull();
});

test('user can reorder routine blocks', function () {
    $user = User::factory()->create();
    $block1 = RoutineBlock::factory()->for($user)->create(['sort_order' => 0]);
    $block2 = RoutineBlock::factory()->for($user)->create(['sort_order' => 1]);

    $this->actingAs($user, 'sanctum')
        ->patchJson('/api/morning-hub/routine/blocks/reorder', [
            'blocks' => [$block2->id, $block1->id],
        ])
        ->assertNoContent();

    expect(RoutineBlock::find($block1->id)->sort_order)->toBe(1);
    expect(RoutineBlock::find($block2->id)->sort_order)->toBe(0);
});
```

- [ ] **Krok 2: Uruchom testy — muszą failować**

```bash
php artisan test --compact --filter=Api\\RoutineBlockTest
```

- [ ] **Krok 3: Utwórz RoutineBlockController**

```bash
php artisan make:class app/Http/Controllers/Api/RoutineBlockController --no-interaction
```

Zawartość `app/Http/Controllers/Api/RoutineBlockController.php`:

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MorningHub\ReorderRoutineBlocksRequest;
use App\Http\Requests\MorningHub\StoreRoutineBlockRequest;
use App\Http\Requests\MorningHub\UpdateRoutineBlockRequest;
use App\Models\RoutineBlock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class RoutineBlockController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'blocks' => $request->user()->routineBlocks()->ordered()->with('clickUpConnection')->get(),
            'connections' => $request->user()->clickUpConnections()->get(),
            'googleCalendarConnectionId' => $request->user()->googleCalendarConnection?->id,
        ]);
    }

    public function store(StoreRoutineBlockRequest $request): JsonResponse
    {
        $nextSortOrder = $request->user()->routineBlocks()->max('sort_order') + 1;

        $block = $request->user()->routineBlocks()->create(
            array_merge($request->validated(), ['sort_order' => $nextSortOrder])
        );

        return response()->json(['block' => $block], 201);
    }

    public function update(UpdateRoutineBlockRequest $request, RoutineBlock $block): JsonResponse
    {
        Gate::authorize('update', $block);
        $block->update($request->validated());

        return response()->json(['block' => $block]);
    }

    public function destroy(Request $request, RoutineBlock $block): Response
    {
        Gate::authorize('delete', $block);
        $block->delete();

        return response()->noContent();
    }

    public function reorder(ReorderRoutineBlocksRequest $request): Response
    {
        foreach ($request->validated('blocks') as $index => $blockId) {
            RoutineBlock::where('id', $blockId)->update(['sort_order' => $index]);
        }

        return response()->noContent();
    }
}
```

- [ ] **Krok 4: Dodaj routes do `routes/api.php`**

```php
use App\Http\Controllers\Api\RoutineBlockController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/morning-hub/routine', [RoutineBlockController::class, 'index']);
    Route::post('/morning-hub/routine/blocks', [RoutineBlockController::class, 'store']);
    Route::put('/morning-hub/routine/blocks/{block}', [RoutineBlockController::class, 'update']);
    Route::delete('/morning-hub/routine/blocks/{block}', [RoutineBlockController::class, 'destroy']);
    Route::patch('/morning-hub/routine/blocks/reorder', [RoutineBlockController::class, 'reorder']);
});
```

- [ ] **Krok 5: Uruchom testy — muszą przechodzić**

```bash
php artisan test --compact --filter=Api\\RoutineBlockTest
```

Oczekiwany output: 7 testów PASS.

- [ ] **Krok 6: Commit**

```bash
git add app/Http/Controllers/Api/RoutineBlockController.php tests/Feature/Api/RoutineBlockTest.php routes/api.php
git commit -m "feat: add api routine blocks endpoints"
```

---

## Task 9: ClickUpConnectionController (API)

**Files:**
- Create: `app/Http/Controllers/Api/ClickUpConnectionController.php`
- Create: `tests/Feature/Api/ClickUpConnectionTest.php`

- [ ] **Krok 1: Napisz testy**

```bash
php artisan make:test --pest Api/ClickUpConnectionTest --no-interaction
```

Zawartość `tests/Feature/Api/ClickUpConnectionTest.php`:

```php
<?php

use App\Models\ClickUpConnection;
use App\Models\User;
use App\Services\ClickUpServiceFactory;

test('guest cannot access clickup connections', function () {
    $this->getJson('/api/morning-hub/clickup')->assertUnauthorized();
});

test('user can fetch their clickup connections', function () {
    $user = User::factory()->create();
    ClickUpConnection::factory()->for($user)->create(['name' => 'My Connection']);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/morning-hub/clickup')
        ->assertOk()
        ->assertJsonCount(1, 'connections')
        ->assertJsonPath('connections.0.name', 'My Connection');
});

test('user can create a clickup connection', function () {
    $user = User::factory()->create();

    $factory = Mockery::mock(ClickUpServiceFactory::class);
    $service = Mockery::mock(\App\Services\ClickUpService::class);
    $service->shouldReceive('testConnection')->andReturn(true);
    $factory->shouldReceive('make')->andReturn($service);
    app()->instance(ClickUpServiceFactory::class, $factory);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/morning-hub/clickup/connections', [
            'name' => 'New Connection',
            'api_token' => 'pk_valid_token',
        ])
        ->assertCreated();
});

test('user cannot create connection with invalid token', function () {
    $user = User::factory()->create();

    $factory = Mockery::mock(ClickUpServiceFactory::class);
    $service = Mockery::mock(\App\Services\ClickUpService::class);
    $service->shouldReceive('testConnection')->andReturn(false);
    $factory->shouldReceive('make')->andReturn($service);
    app()->instance(ClickUpServiceFactory::class, $factory);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/morning-hub/clickup/connections', [
            'name' => 'Bad Connection',
            'api_token' => 'pk_bad_token',
        ])
        ->assertUnprocessable();
});

test('user cannot access another user\'s connection', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $connection = ClickUpConnection::factory()->for($owner)->create();

    $this->actingAs($other, 'sanctum')
        ->deleteJson("/api/morning-hub/clickup/connections/{$connection->id}")
        ->assertForbidden();
});
```

- [ ] **Krok 2: Uruchom testy — muszą failować**

```bash
php artisan test --compact --filter=Api\\ClickUpConnectionTest
```

- [ ] **Krok 3: Utwórz ClickUpConnectionController**

```bash
php artisan make:class app/Http/Controllers/Api/ClickUpConnectionController --no-interaction
```

Zawartość `app/Http/Controllers/Api/ClickUpConnectionController.php`:

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MorningHub\StoreClickUpConnectionRequest;
use App\Http\Requests\MorningHub\UpdateClickUpConnectionRequest;
use App\Models\ClickUpConnection;
use App\Services\ClickUpServiceFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class ClickUpConnectionController extends Controller
{
    public function __construct(
        private readonly ClickUpServiceFactory $clickUpServiceFactory,
    ) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'connections' => $request->user()->clickUpConnections()->latest()->get(),
        ]);
    }

    public function store(StoreClickUpConnectionRequest $request): JsonResponse
    {
        $service = $this->clickUpServiceFactory->make($request->validated('api_token'));

        if (! $service->testConnection()) {
            return response()->json([
                'errors' => ['api_token' => ['The API token is invalid or the connection failed.']],
            ], 422);
        }

        $connection = $request->user()->clickUpConnections()->create($request->validated());

        return response()->json(['connection' => $connection], 201);
    }

    public function update(UpdateClickUpConnectionRequest $request, ClickUpConnection $connection): JsonResponse
    {
        Gate::authorize('update', $connection);

        $data = $request->validated();

        if (isset($data['api_token'])) {
            $service = $this->clickUpServiceFactory->make($data['api_token']);
            if (! $service->testConnection()) {
                return response()->json([
                    'errors' => ['api_token' => ['The API token is invalid or the connection failed.']],
                ], 422);
            }
        }

        if (array_key_exists('default_list_ids', $data)) {
            $data['default_list_id'] = $data['default_list_ids'][0] ?? null;
        }

        $connection->update($data);

        return response()->json(['connection' => $connection]);
    }

    public function destroy(Request $request, ClickUpConnection $connection): Response
    {
        Gate::authorize('delete', $connection);
        $connection->delete();

        return response()->noContent();
    }

    public function test(Request $request, ClickUpConnection $connection): JsonResponse
    {
        Gate::authorize('view', $connection);

        $service = $this->clickUpServiceFactory->make($connection->api_token);
        $success = $service->testConnection();

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Connection successful.' : 'Connection failed.',
        ]);
    }
}
```

- [ ] **Krok 4: Dodaj routes do `routes/api.php`**

```php
use App\Http\Controllers\Api\ClickUpConnectionController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/morning-hub/clickup', [ClickUpConnectionController::class, 'index']);
    Route::post('/morning-hub/clickup/connections', [ClickUpConnectionController::class, 'store'])->middleware('throttle:5,1');
    Route::put('/morning-hub/clickup/connections/{connection}', [ClickUpConnectionController::class, 'update']);
    Route::delete('/morning-hub/clickup/connections/{connection}', [ClickUpConnectionController::class, 'destroy']);
    Route::post('/morning-hub/clickup/connections/{connection}/test', [ClickUpConnectionController::class, 'test'])->middleware('throttle:5,1');
});
```

- [ ] **Krok 5: Uruchom testy — muszą przechodzić**

```bash
php artisan test --compact --filter=Api\\ClickUpConnectionTest
```

Oczekiwany output: 5 testów PASS.

- [ ] **Krok 6: Commit**

```bash
git add app/Http/Controllers/Api/ClickUpConnectionController.php tests/Feature/Api/ClickUpConnectionTest.php routes/api.php
git commit -m "feat: add api clickup connection endpoints"
```

---

## Task 10: ClickUpApiController, GoogleCalendarConnectionController, GoogleCalendarApiController (API)

**Files:**
- Create: `app/Http/Controllers/Api/ClickUpApiController.php`
- Create: `app/Http/Controllers/Api/GoogleCalendarConnectionController.php`
- Create: `app/Http/Controllers/Api/GoogleCalendarApiController.php`
- Create: `tests/Feature/Api/GoogleCalendarConnectionTest.php`

Te kontrolery już zwracają JSON — zmiana polega wyłącznie na nowej przestrzeni nazw i zastąpieniu `Inertia::render()` przez `JsonResponse` w metodzie `index`.

- [ ] **Krok 1: Napisz test dla GoogleCalendarConnection**

```bash
php artisan make:test --pest Api/GoogleCalendarConnectionTest --no-interaction
```

Zawartość `tests/Feature/Api/GoogleCalendarConnectionTest.php`:

```php
<?php

use App\Models\GoogleCalendarConnection;
use App\Models\User;

test('guest cannot access google calendar settings', function () {
    $this->getJson('/api/morning-hub/google-calendar')->assertUnauthorized();
});

test('user can fetch their google calendar connection', function () {
    $user = User::factory()->create();
    GoogleCalendarConnection::factory()->for($user)->create(['name' => 'work@example.com']);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/morning-hub/google-calendar')
        ->assertOk()
        ->assertJsonPath('connection.name', 'work@example.com');
});

test('user can update calendar ids', function () {
    $user = User::factory()->create();
    GoogleCalendarConnection::factory()->for($user)->create();

    $this->actingAs($user, 'sanctum')
        ->putJson('/api/morning-hub/google-calendar', ['calendar_ids' => ['cal-1', 'cal-2']])
        ->assertOk();
});
```

- [ ] **Krok 2: Uruchom testy — muszą failować**

```bash
php artisan test --compact --filter=GoogleCalendarConnectionTest
```

- [ ] **Krok 3: Utwórz ClickUpApiController (API)**

```bash
php artisan make:class app/Http/Controllers/Api/ClickUpApiController --no-interaction
```

Zawartość `app/Http/Controllers/Api/ClickUpApiController.php` — skopiuj logikę z `app/Http/Controllers/MorningHub/ClickUpApiController.php`, zmień tylko namespace:

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClickUpConnection;
use App\Services\ClickUpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ClickUpApiController extends Controller
{
    private function service(ClickUpConnection $connection): ClickUpService
    {
        Gate::authorize('view', $connection);

        return new ClickUpService($connection->api_token);
    }

    public function workspaces(ClickUpConnection $connection): JsonResponse
    {
        return response()->json(['data' => $this->service($connection)->getWorkspaces()]);
    }

    public function spaces(Request $request, ClickUpConnection $connection): JsonResponse
    {
        return response()->json(['data' => $this->service($connection)->getSpaces($request->workspace_id)]);
    }

    public function folders(Request $request, ClickUpConnection $connection): JsonResponse
    {
        return response()->json(['data' => $this->service($connection)->getFolders($request->space_id)]);
    }

    public function lists(Request $request, ClickUpConnection $connection): JsonResponse
    {
        return response()->json(['data' => $this->service($connection)->getLists($request->folder_id)]);
    }

    public function allLists(Request $request, ClickUpConnection $connection): JsonResponse
    {
        return response()->json(['data' => $this->service($connection)->getAllLists($request->space_id)]);
    }

    public function me(ClickUpConnection $connection): JsonResponse
    {
        return response()->json(['data' => $this->service($connection)->getMe()]);
    }

    public function task(ClickUpConnection $connection, string $taskId): JsonResponse
    {
        return response()->json(['data' => $this->service($connection)->getTask($taskId)]);
    }

    public function updateTask(Request $request, ClickUpConnection $connection, string $taskId): JsonResponse
    {
        return response()->json(['data' => $this->service($connection)->updateTask($taskId, $request->all())]);
    }

    public function createTask(Request $request, ClickUpConnection $connection): JsonResponse
    {
        return response()->json(['data' => $this->service($connection)->createTask($request->list_id, $request->all())], 201);
    }

    public function createComment(Request $request, ClickUpConnection $connection, string $taskId): JsonResponse
    {
        return response()->json(['data' => $this->service($connection)->createComment($taskId, $request->all())], 201);
    }

    public function comments(ClickUpConnection $connection, string $taskId): JsonResponse
    {
        return response()->json(['data' => $this->service($connection)->getComments($taskId)]);
    }

    public function statuses(Request $request, ClickUpConnection $connection): JsonResponse
    {
        return response()->json(['data' => $this->service($connection)->getStatuses($request->list_id)]);
    }
}
```

- [ ] **Krok 4: Utwórz GoogleCalendarConnectionController (API)**

```bash
php artisan make:class app/Http/Controllers/Api/GoogleCalendarConnectionController --no-interaction
```

Zawartość `app/Http/Controllers/Api/GoogleCalendarConnectionController.php`:

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MorningHub\UpdateGoogleCalendarConnectionRequest;
use App\Models\GoogleCalendarConnection;
use App\Services\GoogleCalendarServiceFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GoogleCalendarConnectionController extends Controller
{
    public function __construct(
        private readonly GoogleCalendarServiceFactory $googleCalendarServiceFactory,
    ) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'connection' => $request->user()->googleCalendarConnection,
        ]);
    }

    public function update(UpdateGoogleCalendarConnectionRequest $request): JsonResponse
    {
        $connection = $request->user()->googleCalendarConnection;

        if (! $connection) {
            return response()->json(['message' => 'No Google Calendar connection found.'], 404);
        }

        $connection->update($request->validated());

        return response()->json(['connection' => $connection]);
    }

    public function test(Request $request): JsonResponse
    {
        $connection = $request->user()->googleCalendarConnection;

        if (! $connection) {
            return response()->json(['success' => false, 'message' => 'No connection found.']);
        }

        try {
            $service = $this->googleCalendarServiceFactory->make($connection);
            $service->getCalendars();
            $success = true;
            $message = 'Connection successful.';
        } catch (\Throwable) {
            $success = false;
            $message = 'Connection failed.';
        }

        return response()->json(['success' => $success, 'message' => $message]);
    }
}
```

- [ ] **Krok 5: Utwórz GoogleCalendarApiController (API)**

```bash
php artisan make:class app/Http/Controllers/Api/GoogleCalendarApiController --no-interaction
```

Zawartość `app/Http/Controllers/Api/GoogleCalendarApiController.php`:

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GoogleCalendarServiceFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GoogleCalendarApiController extends Controller
{
    public function __construct(
        private readonly GoogleCalendarServiceFactory $googleCalendarServiceFactory,
    ) {}

    public function calendars(Request $request): JsonResponse
    {
        $connection = $request->user()->googleCalendarConnection;

        if (! $connection) {
            return response()->json(['calendars' => []]);
        }

        if ($connection->isTokenExpired()) {
            return response()->json(['error' => 'auth_expired', 'calendars' => []], 401);
        }

        return response()->json([
            'calendars' => $this->googleCalendarServiceFactory->make($connection)->getCalendars(),
        ]);
    }
}
```

- [ ] **Krok 6: Dodaj routes do `routes/api.php`**

```php
use App\Http\Controllers\Api\ClickUpApiController;
use App\Http\Controllers\Api\GoogleCalendarConnectionController;
use App\Http\Controllers\Api\GoogleCalendarApiController;
use App\Http\Middleware\LogApiProxyAccess;

Route::middleware('auth:sanctum')->group(function () {
    // Google Calendar connection
    Route::get('/morning-hub/google-calendar', [GoogleCalendarConnectionController::class, 'index']);
    Route::put('/morning-hub/google-calendar', [GoogleCalendarConnectionController::class, 'update']);
    Route::post('/morning-hub/google-calendar/test', [GoogleCalendarConnectionController::class, 'test'])->middleware('throttle:5,1');

    // Google Calendar API proxy
    Route::get('/morning-hub/google-calendar/calendars', [GoogleCalendarApiController::class, 'calendars'])->middleware(['throttle:60,1', LogApiProxyAccess::class]);

    // ClickUp API proxy
    Route::middleware(['throttle:60,1', LogApiProxyAccess::class])->group(function () {
        Route::get('/morning-hub/clickup/{connection}/workspaces', [ClickUpApiController::class, 'workspaces']);
        Route::get('/morning-hub/clickup/{connection}/spaces', [ClickUpApiController::class, 'spaces']);
        Route::get('/morning-hub/clickup/{connection}/folders', [ClickUpApiController::class, 'folders']);
        Route::get('/morning-hub/clickup/{connection}/lists', [ClickUpApiController::class, 'lists']);
        Route::get('/morning-hub/clickup/{connection}/all-lists', [ClickUpApiController::class, 'allLists']);
        Route::get('/morning-hub/clickup/{connection}/me', [ClickUpApiController::class, 'me']);
        Route::get('/morning-hub/clickup/{connection}/tasks/{taskId}', [ClickUpApiController::class, 'task']);
        Route::put('/morning-hub/clickup/{connection}/tasks/{taskId}', [ClickUpApiController::class, 'updateTask']);
        Route::post('/morning-hub/clickup/{connection}/tasks', [ClickUpApiController::class, 'createTask']);
        Route::post('/morning-hub/clickup/{connection}/tasks/{taskId}/comments', [ClickUpApiController::class, 'createComment']);
        Route::get('/morning-hub/clickup/{connection}/tasks/{taskId}/comments', [ClickUpApiController::class, 'comments']);
        Route::get('/morning-hub/clickup/{connection}/statuses', [ClickUpApiController::class, 'statuses']);
    });
});
```

- [ ] **Krok 7: Uruchom testy — muszą przechodzić**

```bash
php artisan test --compact --filter=GoogleCalendarConnectionTest
```

Oczekiwany output: 3 testy PASS.

- [ ] **Krok 8: Commit**

```bash
git add app/Http/Controllers/Api/ClickUpApiController.php app/Http/Controllers/Api/GoogleCalendarConnectionController.php app/Http/Controllers/Api/GoogleCalendarApiController.php tests/Feature/Api/GoogleCalendarConnectionTest.php routes/api.php
git commit -m "feat: add api clickup proxy and google calendar endpoints"
```

---

## Task 11: TodaysTasksConfigController + Settings Controllers (API)

**Files:**
- Create: `app/Http/Controllers/Api/TodaysTasksConfigController.php`
- Create: `app/Http/Controllers/Api/Settings/ProfileController.php`
- Create: `app/Http/Controllers/Api/Settings/PasswordController.php`
- Create: `app/Http/Controllers/Api/Settings/TwoFactorAuthenticationController.php`
- Create: `app/Http/Controllers/Api/Settings/AppearanceController.php`
- Create: `tests/Feature/Api/TodaysTasksConfigTest.php`
- Create: `tests/Feature/Api/Settings/ProfileTest.php`
- Create: `tests/Feature/Api/Settings/PasswordTest.php`
- Create: `tests/Feature/Api/Settings/TwoFactorTest.php`

- [ ] **Krok 1: Napisz testy**

```bash
php artisan make:test --pest Api/TodaysTasksConfigTest --no-interaction
php artisan make:test --pest Api/Settings/ProfileTest --no-interaction
php artisan make:test --pest Api/Settings/PasswordTest --no-interaction
php artisan make:test --pest Api/Settings/TwoFactorTest --no-interaction
```

Zawartość `tests/Feature/Api/TodaysTasksConfigTest.php`:

```php
<?php

use App\Models\ClickUpConnection;
use App\Models\TodaysTasksConfig;
use App\Models\User;

test('user can fetch todays tasks config', function () {
    $user = User::factory()->create();
    TodaysTasksConfig::factory()->for($user)->create();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/morning-hub/todays-tasks')
        ->assertOk()
        ->assertJsonStructure(['config']);
});

test('user can update todays tasks config', function () {
    $user = User::factory()->create();
    $connection = ClickUpConnection::factory()->for($user)->create();

    $this->actingAs($user, 'sanctum')
        ->putJson('/api/morning-hub/todays-tasks', ['connection_ids' => [$connection->id]])
        ->assertOk();
});
```

Zawartość `tests/Feature/Api/Settings/ProfileTest.php`:

```php
<?php

use App\Models\User;

test('user can fetch profile', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/settings/profile')
        ->assertOk()
        ->assertJsonStructure(['user' => ['id', 'name', 'email']]);
});

test('user can update profile', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->patchJson('/api/settings/profile', ['name' => 'New Name', 'email' => $user->email])
        ->assertOk();

    expect($user->fresh()->name)->toBe('New Name');
});

test('user can delete account', function () {
    $user = User::factory()->create(['password' => bcrypt('password')]);

    $this->actingAs($user, 'sanctum')
        ->deleteJson('/api/settings/profile', ['password' => 'password'])
        ->assertNoContent();

    expect(User::find($user->id))->toBeNull();
});
```

Zawartość `tests/Feature/Api/Settings/PasswordTest.php`:

```php
<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('user can update password', function () {
    $user = User::factory()->create(['password' => Hash::make('old-password')]);

    $this->actingAs($user, 'sanctum')
        ->putJson('/api/settings/password', [
            'current_password' => 'old-password',
            'password' => 'NewPassword1!',
            'password_confirmation' => 'NewPassword1!',
        ])
        ->assertOk();
});

test('update password fails with wrong current password', function () {
    $user = User::factory()->create(['password' => Hash::make('old-password')]);

    $this->actingAs($user, 'sanctum')
        ->putJson('/api/settings/password', [
            'current_password' => 'wrong',
            'password' => 'NewPassword1!',
            'password_confirmation' => 'NewPassword1!',
        ])
        ->assertUnprocessable();
});
```

Zawartość `tests/Feature/Api/Settings/TwoFactorTest.php`:

```php
<?php

use App\Models\User;

test('user can fetch 2fa status', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/settings/two-factor')
        ->assertOk()
        ->assertJsonStructure(['twoFactorEnabled', 'requiresConfirmation']);
});
```

- [ ] **Krok 2: Uruchom testy — muszą failować**

```bash
php artisan test --compact --filter="TodaysTasksConfigTest|Settings"
```

- [ ] **Krok 3: Utwórz TodaysTasksConfigController**

```bash
php artisan make:class app/Http/Controllers/Api/TodaysTasksConfigController --no-interaction
```

Zawartość `app/Http/Controllers/Api/TodaysTasksConfigController.php`:

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MorningHub\UpdateTodaysTasksConfigRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TodaysTasksConfigController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'config' => $request->user()->todaysTasksConfig,
        ]);
    }

    public function update(UpdateTodaysTasksConfigRequest $request): JsonResponse
    {
        $config = $request->user()->todaysTasksConfig()->updateOrCreate(
            ['user_id' => $request->user()->id],
            $request->validated()
        );

        return response()->json(['config' => $config]);
    }
}
```

- [ ] **Krok 4: Utwórz Settings controllers**

```bash
php artisan make:class app/Http/Controllers/Api/Settings/ProfileController --no-interaction
php artisan make:class app/Http/Controllers/Api/Settings/PasswordController --no-interaction
php artisan make:class app/Http/Controllers/Api/Settings/TwoFactorAuthenticationController --no-interaction
php artisan make:class app/Http/Controllers/Api/Settings/AppearanceController --no-interaction
```

Zawartość `app/Http/Controllers/Api/Settings/ProfileController.php`:

```php
<?php

namespace App\Http\Controllers\Api\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileDeleteRequest;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $request->user()->only(['id', 'name', 'email', 'google_id', 'google_avatar', 'email_verified_at']),
        ]);
    }

    public function update(ProfileUpdateRequest $request): JsonResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return response()->json([
            'user' => $request->user()->only(['id', 'name', 'email']),
        ]);
    }

    public function destroy(ProfileDeleteRequest $request): Response
    {
        $user = $request->user();
        $user->currentAccessToken()->delete();
        $user->delete();

        return response()->noContent();
    }
}
```

Zawartość `app/Http/Controllers/Api/Settings/PasswordController.php`:

```php
<?php

namespace App\Http\Controllers\Api\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\PasswordUpdateRequest;
use Illuminate\Http\JsonResponse;

class PasswordController extends Controller
{
    public function update(PasswordUpdateRequest $request): JsonResponse
    {
        $request->user()->update(['password' => $request->password]);

        return response()->json(['message' => 'Password updated.']);
    }
}
```

Zawartość `app/Http/Controllers/Api/Settings/TwoFactorAuthenticationController.php`:

```php
<?php

namespace App\Http\Controllers\Api\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\TwoFactorAuthenticationRequest;
use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Features;

class TwoFactorAuthenticationController extends Controller
{
    public function show(TwoFactorAuthenticationRequest $request): JsonResponse
    {
        $request->ensureStateIsValid();

        return response()->json([
            'twoFactorEnabled' => $request->user()->hasEnabledTwoFactorAuthentication(),
            'requiresConfirmation' => Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm'),
        ]);
    }
}
```

Zawartość `app/Http/Controllers/Api/Settings/AppearanceController.php`:

```php
<?php

namespace App\Http\Controllers\Api\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AppearanceController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'appearance' => $request->cookie('appearance', 'system'),
        ]);
    }

    public function update(Request $request): Response
    {
        $request->validate(['appearance' => ['required', 'in:light,dark,system']]);

        return response()->noContent()->cookie('appearance', $request->appearance, 60 * 24 * 365);
    }
}
```

- [ ] **Krok 5: Dodaj routes do `routes/api.php`**

```php
use App\Http\Controllers\Api\TodaysTasksConfigController;
use App\Http\Controllers\Api\Settings\ProfileController;
use App\Http\Controllers\Api\Settings\PasswordController;
use App\Http\Controllers\Api\Settings\TwoFactorAuthenticationController;
use App\Http\Controllers\Api\Settings\AppearanceController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/morning-hub/todays-tasks', [TodaysTasksConfigController::class, 'index']);
    Route::put('/morning-hub/todays-tasks', [TodaysTasksConfigController::class, 'update']);

    Route::get('/settings/profile', [ProfileController::class, 'show']);
    Route::patch('/settings/profile', [ProfileController::class, 'update']);
    Route::delete('/settings/profile', [ProfileController::class, 'destroy']);

    Route::put('/settings/password', [PasswordController::class, 'update'])->middleware('throttle:6,1');

    Route::get('/settings/two-factor', [TwoFactorAuthenticationController::class, 'show']);

    Route::get('/settings/appearance', [AppearanceController::class, 'show']);
    Route::patch('/settings/appearance', [AppearanceController::class, 'update']);
});
```

- [ ] **Krok 6: Uruchom testy — muszą przechodzić**

```bash
php artisan test --compact --filter="TodaysTasksConfigTest|Api\\Settings"
```

Oczekiwany output: 8 testów PASS.

- [ ] **Krok 7: Commit**

```bash
git add app/Http/Controllers/Api/TodaysTasksConfigController.php app/Http/Controllers/Api/Settings/ tests/Feature/Api/TodaysTasksConfigTest.php tests/Feature/Api/Settings/ routes/api.php
git commit -m "feat: add api todays tasks config and settings endpoints"
```

---

## Task 12: Dashboard i TodaysTasks (API)

**Files:**
- Create: `app/Http/Controllers/Api/DashboardController.php`
- Create: `app/Http/Controllers/Api/TodaysTasksController.php`
- Create: `tests/Feature/Api/DashboardTest.php`

Dashboard zwraca bloki + konfigurację. Dane dla bloków (ClickUp tasks, feed, calendar events) są pobierane przez frontend przez istniejące endpointy proxy — nie są już ładowane przez backend lazy.

- [ ] **Krok 1: Napisz testy**

```bash
php artisan make:test --pest Api/DashboardTest --no-interaction
```

Zawartość `tests/Feature/Api/DashboardTest.php`:

```php
<?php

use App\Models\RoutineBlock;
use App\Models\User;

test('guest cannot access dashboard', function () {
    $this->getJson('/api/dashboard')->assertUnauthorized();
});

test('user can fetch dashboard data', function () {
    $user = User::factory()->create();
    RoutineBlock::factory()->for($user)->count(2)->create();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/dashboard')
        ->assertOk()
        ->assertJsonStructure(['blocks']);
});

test('guest cannot access todays tasks', function () {
    $this->getJson('/api/todays-tasks')->assertUnauthorized();
});

test('user can fetch todays tasks data', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/todays-tasks')
        ->assertOk()
        ->assertJsonStructure(['config', 'connections']);
});
```

- [ ] **Krok 2: Uruchom testy — muszą failować**

```bash
php artisan test --compact --filter=Api\\DashboardTest
```

- [ ] **Krok 3: Utwórz DashboardController (API)**

```bash
php artisan make:class app/Http/Controllers/Api/DashboardController --no-interaction
```

Zawartość `app/Http/Controllers/Api/DashboardController.php`:

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $blocks = $request->user()
            ->routineBlocks()
            ->ordered()
            ->with(['clickUpConnection', 'googleCalendarConnection'])
            ->get();

        return response()->json(['blocks' => $blocks]);
    }
}
```

- [ ] **Krok 4: Utwórz TodaysTasksController (API)**

```bash
php artisan make:class app/Http/Controllers/Api/TodaysTasksController --no-interaction
```

Zawartość `app/Http/Controllers/Api/TodaysTasksController.php`:

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TodaysTasksController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $config = $request->user()->todaysTasksConfig;
        $connectionIds = $config?->connection_ids ?? [];

        $connections = $request->user()
            ->clickUpConnections()
            ->whereIn('id', $connectionIds)
            ->get();

        return response()->json([
            'config' => $config,
            'connections' => $connections,
        ]);
    }
}
```

- [ ] **Krok 5: Dodaj routes do `routes/api.php`**

```php
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\TodaysTasksController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/dashboard', DashboardController::class);
    Route::get('/todays-tasks', TodaysTasksController::class);
});
```

- [ ] **Krok 6: Uruchom testy — muszą przechodzić**

```bash
php artisan test --compact --filter=Api\\DashboardTest
```

Oczekiwany output: 4 testy PASS.

- [ ] **Krok 7: Commit**

```bash
git add app/Http/Controllers/Api/DashboardController.php app/Http/Controllers/Api/TodaysTasksController.php tests/Feature/Api/DashboardTest.php routes/api.php
git commit -m "feat: add api dashboard and todays-tasks endpoints"
```

---

## Task 13: Konsolidacja routingu — web.php + bootstrap/app.php

**Files:**
- Modify: `routes/web.php`
- Modify: `bootstrap/app.php`

- [ ] **Krok 1: Zastąp `routes/web.php`**

Zawartość `routes/web.php` po refaktorze:

```php
<?php

use App\Http\Controllers\MorningHub\GoogleCalendarOAuthController;
use Illuminate\Support\Facades\Route;

// Google Calendar OAuth — server-side redirect (Google requires HTTPS callback URL)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('morning-hub/google-calendar/connect', [GoogleCalendarOAuthController::class, 'redirect'])
        ->middleware('throttle:5,1')
        ->name('morning-hub.google-calendar.connect');
    Route::get('morning-hub/google-calendar/callback', [GoogleCalendarOAuthController::class, 'callback'])
        ->name('morning-hub.google-calendar.callback');
    Route::delete('morning-hub/google-calendar/disconnect', [GoogleCalendarOAuthController::class, 'disconnect'])
        ->middleware('throttle:5,1')
        ->name('morning-hub.google-calendar.disconnect');
});

// Google account linking — server-side redirect (same reason)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('auth/google/link', [\App\Http\Controllers\Auth\GoogleAuthController::class, 'linkRedirect'])->name('google.link');
    Route::get('auth/google/link/callback', [\App\Http\Controllers\Auth\GoogleAuthController::class, 'linkCallback'])->name('google.link.callback');
    Route::delete('auth/google/unlink', [\App\Http\Controllers\Auth\GoogleAuthController::class, 'unlink'])->name('google.unlink');
});

// SPA catch-all — wszystkie pozostałe ścieżki obsługuje Vue Router
Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*')->name('spa');
```

- [ ] **Krok 2: Zaktualizuj `bootstrap/app.php`**

Nowa zawartość `bootstrap/app.php`:

```php
<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleLocale;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Log;
use Sentry\Laravel\Integration;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state', 'locale']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleLocale::class,
        ]);

        $middleware->api(append: [
            HandleLocale::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        Integration::handles($exceptions);

        $exceptions->dontFlash([
            'api_token',
            'password',
            'password_confirmation',
        ]);

        $exceptions->reportable(function (AuthorizationException $e): void {
            Log::channel('security')->warning('Authorization denied', [
                'user_id' => auth()->id(),
                'url' => request()->fullUrl(),
                'method' => request()->method(),
                'ip' => request()->ip(),
            ]);
        });
    })->create();
```

- [ ] **Krok 3: Uruchom wszystkie testy API by upewnić się że nic się nie popsuło**

```bash
php artisan test --compact tests/Feature/Api/
```

Oczekiwany output: wszystkie testy PASS.

- [ ] **Krok 4: Commit**

```bash
git add routes/web.php bootstrap/app.php
git commit -m "refactor: simplify web routes to spa catch-all, register api routes"
```

---

## Task 14: Przepisanie istniejących testów Inertia → JSON

**Files:**
- Modify: `tests/Feature/MorningHub/RoutineBlockTest.php`
- Modify: `tests/Feature/MorningHub/ClickUpConnectionTest.php`
- Modify: `tests/Feature/MorningHub/GoogleCalendarConnectionTest.php`
- Modify: `tests/Feature/MorningHub/TodaysTasksConfigTest.php`
- Modify: `tests/Feature/Settings/ProfileUpdateTest.php`
- Modify: `tests/Feature/Settings/PasswordUpdateTest.php`
- Modify: `tests/Feature/Settings/TwoFactorAuthenticationTest.php`
- Modify: `tests/Feature/DashboardTest.php`
- Modify: `tests/Feature/Auth/AuthenticationTest.php`

> **Uwaga:** Stare testy Inertia dla tych samych zasobów teraz są zdublowane z nowymi testami API (tasks 8–12). Stare testy należy **usunąć** (nie modyfikować) — logika jest pokryta przez nowe testy w `tests/Feature/Api/`.

- [ ] **Krok 1: Sprawdź które stare testy nadal mają sens**

```bash
php artisan test --compact tests/Feature/MorningHub/ tests/Feature/Settings/ tests/Feature/DashboardTest.php
```

Testy które używają `assertInertia()` lub `route('morning-hub.*')` będą failować — to oczekiwane.

- [ ] **Krok 2: Usuń zdublowane pliki testów MorningHub i Settings**

```bash
rm tests/Feature/MorningHub/RoutineBlockTest.php
rm tests/Feature/MorningHub/ClickUpConnectionTest.php
rm tests/Feature/MorningHub/GoogleCalendarConnectionTest.php
rm tests/Feature/MorningHub/GoogleCalendarDashboardTest.php
rm tests/Feature/MorningHub/TodaysTasksConfigTest.php
rm tests/Feature/MorningHub/GuideTest.php
rm tests/Feature/Settings/ProfileUpdateTest.php
rm tests/Feature/Settings/PasswordUpdateTest.php
rm tests/Feature/Settings/TwoFactorAuthenticationTest.php
rm tests/Feature/DashboardTest.php
rm tests/Feature/TodaysTasksTest.php
```

- [ ] **Krok 3: Zaktualizuj testy Auth**

Testy w `tests/Feature/Auth/` testują Fortify web routes (login redirect, session). Fortify routes dla web są nadal aktywne (Fortify rejestruje je automatycznie). Zaktualizuj `tests/Feature/Auth/AuthenticationTest.php` — zostaw tylko testy które nadal działają przez Fortify lub usuń całość jeśli Fortify web routes będą usunięte w dalszym etapie.

Sprawdź czy Fortify routes są aktywne:

```bash
php artisan route:list --name=login
```

Jeśli `login` route istnieje (Fortify go rejestruje) → testy `AuthenticationTest.php` nadal przechodzą bez zmian.

- [ ] **Krok 4: Uruchom wszystkie testy — zero failów**

```bash
php artisan test --compact
```

Oczekiwany output: wszystkie testy PASS (żadnych `assertInertia` błędów).

- [ ] **Krok 5: Commit**

```bash
git add -A
git commit -m "refactor: remove inertia tests, covered by api test suite"
```

---

## Task 15: Usunięcie HandleInertiaRequests + cleanup

**Files:**
- Delete: `app/Http/Middleware/HandleInertiaRequests.php`
- Delete: `routes/morning-hub.php`
- Delete: `routes/settings.php`

- [ ] **Krok 1: Usuń middleware i stare pliki routingu**

```bash
rm app/Http/Middleware/HandleInertiaRequests.php
rm routes/morning-hub.php
rm routes/settings.php
```

- [ ] **Krok 2: Usuń stare kontrolery Inertia (opcjonalnie, po weryfikacji)**

Sprawdź że żaden test ani route nie używa już starych kontrolerów:

```bash
php artisan route:list | grep -v "api\|_ignition\|sanctum\|fortify"
```

Jeśli w outputcie są tylko web routes (Google Calendar OAuth, Google link, SPA catch-all) → bezpiecznie usunąć:

```bash
rm app/Http/Controllers/DashboardController.php
rm app/Http/Controllers/TodaysTasksController.php
rm app/Http/Controllers/GuideController.php
rm app/Http/Controllers/SetLocaleController.php
rm -rf app/Http/Controllers/MorningHub/
rm app/Http/Controllers/Settings/ProfileController.php
rm app/Http/Controllers/Settings/PasswordController.php
rm app/Http/Controllers/Settings/TwoFactorAuthenticationController.php
```

Zachowaj `app/Http/Controllers/Auth/GoogleAuthController.php` — nadal używany przez Google link/unlink web routes.

- [ ] **Krok 3: Uruchom wszystkie testy**

```bash
php artisan test --compact
```

Oczekiwany output: wszystkie testy PASS.

- [ ] **Krok 4: Pint + PHPStan**

```bash
vendor/bin/pint --dirty --format agent
vendor/bin/phpstan analyse --error-format=table
```

Napraw wszystkie błędy PHPStan przed kontynuowaniem.

- [ ] **Krok 5: Commit końcowy**

```bash
git add -A
git commit -m "refactor: remove inertia middleware and old controllers"
```

---

## Task 16: Weryfikacja końcowa

- [ ] **Krok 1: Uruchom pełny test suite**

```bash
php artisan test --compact
```

Oczekiwany output: wszystkie testy PASS, zero błędów.

- [ ] **Krok 2: Sprawdź listę routesów API**

```bash
php artisan route:list --path=api
```

Upewnij się że wszystkie endpointy z sekcji 3.2 specyfikacji są widoczne.

- [ ] **Krok 3: Sprawdź PHPStan poziom 5**

```bash
vendor/bin/phpstan analyse --error-format=table
```

Zero błędów.

- [ ] **Krok 4: Sprawdź Pint**

```bash
vendor/bin/pint --test --format agent
```

Zero błędów formatowania.

- [ ] **Krok 5: Commit końcowy Etapu 1**

```bash
git add -A
git commit -m "feat: complete backend api refactor (etap 1)"
```

---

## Notatki implementacyjne

**Sanctum API prefix:** Laravel domyślnie dodaje prefix `/api` do routów zarejestrowanych w `routes/api.php`. Wszystkie endpointy w tym planie są pisane bez prefixu `/api` — Laravel dodaje go automatycznie.

**Fortify routes:** Fortify rejestruje własne web routes (login, register, password reset, 2FA management) przez `FortifyServiceProvider`. Te routes są nadal aktywne po refaktorze — nie przeszkadzają API, ale nie są używane przez nowy frontend SPA. Można je wyłączyć w `config/fortify.php` w Etapie 2 gdy frontend SPA będzie gotowy.

**Google Calendar OAuth callback:** `GoogleCalendarOAuthController` pozostaje bez zmian — nadal używa Socialite server-side redirect.

**Password hash:** `LoginController` sprawdza `$user->password ?? ''` ponieważ użytkownicy Google-only nie mają hasła (null). Hash::check() na null powoduje błąd.

**Kolejność routes w api.php:** Route `GET /morning-hub/routine/blocks/reorder` musi być zarejestrowany **przed** `GET /morning-hub/routine/blocks/{block}` żeby Laravel nie traktował `reorder` jako parametru `{block}`. W planie używamy `PATCH` dla reorder więc konflikt nie występuje.
