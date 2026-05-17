# ClickUp OAuth Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the manual `pk_` token form for ClickUp connections with a standard OAuth 2.0 Authorization Code flow.

**Architecture:** A new `ClickUpOAuthService` handles URL generation and token exchange. A new `ClickUpOAuthController` (web route, session auth) orchestrates the redirect and callback. The existing `ClickUpService` and all API proxy endpoints are untouched — only the _creation_ path changes.

**Tech Stack:** Laravel 12, Http facade, session-based auth (web guard), Vue 3 + `vue-sonner` toasts, `useRoute`/`useRouter` from `vue-router`.

---

### Task 1: Add ClickUp to services config + create ClickUpOAuthService

**Files:**
- Modify: `config/services.php`
- Create: `app/Services/ClickUpOAuthService.php`
- Create: `tests/Unit/ClickUpOAuthServiceTest.php`

- [ ] **Step 1: Write the failing tests**

Run: `php artisan make:test --pest --unit ClickUpOAuthServiceTest`

Replace the file content with:

```php
<?php

use App\Services\ClickUpOAuthService;
use Illuminate\Support\Facades\Http;

test('buildAuthorizationUrl contains client_id, redirect_uri and state', function () {
    config([
        'services.clickup.client_id' => 'test_client',
        'services.clickup.redirect' => 'http://localhost/clickup/oauth/callback',
    ]);

    $service = new ClickUpOAuthService();
    $url = $service->buildAuthorizationUrl('state_abc123');

    expect($url)
        ->toContain('app.clickup.com/api')
        ->toContain('client_id=test_client')
        ->toContain('state=state_abc123')
        ->toContain('redirect_uri=');
});

test('exchangeToken returns access token string on success', function () {
    Http::fake([
        'https://api.clickup.com/api/v2/oauth/token' => Http::response([
            'access_token' => 'oauth_abc123',
        ], 200),
    ]);

    config([
        'services.clickup.client_id' => 'test_client',
        'services.clickup.client_secret' => 'test_secret',
    ]);

    $service = new ClickUpOAuthService();
    $token = $service->exchangeToken('auth_code_xyz');

    expect($token)->toBe('oauth_abc123');
});

test('exchangeToken throws RuntimeException on failed response', function () {
    Http::fake([
        'https://api.clickup.com/api/v2/oauth/token' => Http::response([
            'error' => 'invalid_code',
        ], 400),
    ]);

    $service = new ClickUpOAuthService();

    expect(fn () => $service->exchangeToken('bad_code'))
        ->toThrow(\RuntimeException::class);
});

test('exchangeToken throws RuntimeException when access_token missing from response', function () {
    Http::fake([
        'https://api.clickup.com/api/v2/oauth/token' => Http::response([], 200),
    ]);

    $service = new ClickUpOAuthService();

    expect(fn () => $service->exchangeToken('code_no_token'))
        ->toThrow(\RuntimeException::class);
});
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
php artisan test --compact --filter=ClickUpOAuthServiceTest
```

Expected: FAIL — class `ClickUpOAuthService` not found.

- [ ] **Step 3: Add clickup to services config**

In `config/services.php`, after the `google` block add:

```php
'clickup' => [
    'client_id' => env('CLICKUP_CLIENT_ID'),
    'client_secret' => env('CLICKUP_CLIENT_SECRET'),
    'redirect' => env('CLICKUP_REDIRECT_URI', '/api/clickup/callback'),
],
```

- [ ] **Step 4: Create ClickUpOAuthService**

```bash
php artisan make:class app/Services/ClickUpOAuthService --no-interaction
```

Replace the file content with:

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ClickUpOAuthService
{
    private const AUTH_URL = 'https://app.clickup.com/api';
    private const TOKEN_URL = 'https://api.clickup.com/api/v2/oauth/token';

    public function buildAuthorizationUrl(string $state): string
    {
        return self::AUTH_URL.'?'.http_build_query([
            'client_id' => config('services.clickup.client_id'),
            'redirect_uri' => config('services.clickup.redirect'),
            'state' => $state,
        ]);
    }

    public function exchangeToken(string $code): string
    {
        $response = Http::post(self::TOKEN_URL, [
            'client_id' => config('services.clickup.client_id'),
            'client_secret' => config('services.clickup.client_secret'),
            'code' => $code,
        ]);

        $token = $response->json('access_token');

        if (! $response->successful() || ! $token) {
            throw new \RuntimeException('ClickUp token exchange failed.');
        }

        return $token;
    }
}
```

- [ ] **Step 5: Run tests to verify they pass**

```bash
php artisan test --compact --filter=ClickUpOAuthServiceTest
```

Expected: 4 tests PASS.

- [ ] **Step 6: Run PHPStan and Pint**

```bash
vendor/bin/phpstan analyse --error-format=table app/Services/ClickUpOAuthService.php
vendor/bin/pint --dirty --format agent
```

Expected: no errors.

- [ ] **Step 7: Commit**

```bash
git add config/services.php app/Services/ClickUpOAuthService.php tests/Unit/ClickUpOAuthServiceTest.php
git commit -m "feat: add ClickUpOAuthService and services config"
```

---

### Task 2: ClickUpOAuthController + web routes

**Files:**
- Create: `app/Http/Controllers/MorningHub/ClickUpOAuthController.php`
- Modify: `routes/web.php`
- Create: `tests/Feature/MorningHub/ClickUpOAuthTest.php`

- [ ] **Step 1: Write the failing tests**

```bash
php artisan make:test --pest MorningHub/ClickUpOAuthTest --no-interaction
```

Replace the file content with:

```php
<?php

use App\Models\ClickUpConnection;
use App\Models\User;
use Illuminate\Support\Facades\Http;

test('redirect requires authentication', function () {
    $this->get('/clickup/oauth/redirect')
        ->assertRedirect('/login');
});

test('redirect stores state and name in session and redirects to clickup', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get('/clickup/oauth/redirect?name=Praca');

    $response->assertRedirect();
    expect($response->headers->get('Location'))->toContain('app.clickup.com/api');
    expect(session('clickup_oauth_state'))->not->toBeNull();
    expect(session('clickup_oauth_name'))->toBe('Praca');
});

test('redirect uses default name when name param is missing', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/clickup/oauth/redirect');

    expect(session('clickup_oauth_name'))->toBe('ClickUp');
});

test('callback creates connection and redirects with connected=1', function () {
    Http::fake([
        'https://api.clickup.com/api/v2/oauth/token' => Http::response([
            'access_token' => 'oauth_token_abc',
        ], 200),
    ]);

    $user = User::factory()->create();
    $state = 'test_state_xyz';

    $response = $this->actingAs($user)
        ->withSession([
            'clickup_oauth_state' => $state,
            'clickup_oauth_name' => 'Praca',
        ])
        ->get('/clickup/oauth/callback?code=auth_code&state='.$state);

    $response->assertRedirect('/morning-hub/clickup?connected=1');

    $this->assertDatabaseHas('clickup_connections', [
        'user_id' => $user->id,
        'name' => 'Praca',
    ]);
});

test('callback clears oauth session keys after success', function () {
    Http::fake([
        'https://api.clickup.com/api/v2/oauth/token' => Http::response([
            'access_token' => 'oauth_token_abc',
        ], 200),
    ]);

    $user = User::factory()->create();
    $state = 'test_state_xyz';

    $this->actingAs($user)
        ->withSession([
            'clickup_oauth_state' => $state,
            'clickup_oauth_name' => 'Praca',
        ])
        ->get('/clickup/oauth/callback?code=auth_code&state='.$state);

    expect(session('clickup_oauth_state'))->toBeNull();
    expect(session('clickup_oauth_name'))->toBeNull();
});

test('callback redirects with no_code error when code is missing', function () {
    $user = User::factory()->create();
    $state = 'test_state_xyz';

    $this->actingAs($user)
        ->withSession(['clickup_oauth_state' => $state])
        ->get('/clickup/oauth/callback?state='.$state)
        ->assertRedirect('/morning-hub/clickup?error=no_code');
});

test('callback redirects with invalid_state error on state mismatch', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession(['clickup_oauth_state' => 'correct_state'])
        ->get('/clickup/oauth/callback?code=abc&state=wrong_state')
        ->assertRedirect('/morning-hub/clickup?error=invalid_state');
});

test('callback redirects with auth_failed when token exchange fails', function () {
    Http::fake([
        'https://api.clickup.com/api/v2/oauth/token' => Http::response([
            'error' => 'invalid_code',
        ], 400),
    ]);

    $user = User::factory()->create();
    $state = 'test_state_xyz';

    $this->actingAs($user)
        ->withSession([
            'clickup_oauth_state' => $state,
            'clickup_oauth_name' => 'Praca',
        ])
        ->get('/clickup/oauth/callback?code=bad_code&state='.$state)
        ->assertRedirect('/morning-hub/clickup?error=auth_failed');
});
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
php artisan test --compact --filter=ClickUpOAuthTest
```

Expected: FAIL — routes not found (404).

- [ ] **Step 3: Create the controller**

```bash
php artisan make:controller MorningHub/ClickUpOAuthController --no-interaction
```

Replace the file content with:

```php
<?php

namespace App\Http\Controllers\MorningHub;

use App\Http\Controllers\Controller;
use App\Services\ClickUpOAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ClickUpOAuthController extends Controller
{
    public function __construct(
        private readonly ClickUpOAuthService $oauthService,
    ) {}

    public function redirect(Request $request): RedirectResponse
    {
        $name = $request->query('name', 'ClickUp');
        $state = Str::random(40);

        $request->session()->put('clickup_oauth_state', $state);
        $request->session()->put('clickup_oauth_name', $name);

        return redirect($this->oauthService->buildAuthorizationUrl($state));
    }

    public function callback(Request $request): RedirectResponse
    {
        $frontendUrl = '/morning-hub/clickup';

        if (! $request->has('code')) {
            return redirect($frontendUrl.'?error=no_code');
        }

        if ($request->query('state') !== $request->session()->pull('clickup_oauth_state')) {
            $request->session()->forget('clickup_oauth_name');

            return redirect($frontendUrl.'?error=invalid_state');
        }

        $name = $request->session()->pull('clickup_oauth_name', 'ClickUp');

        try {
            $token = $this->oauthService->exchangeToken($request->query('code'));
        } catch (\RuntimeException) {
            return redirect($frontendUrl.'?error=auth_failed');
        }

        /** @var \App\Models\User $user */
        $user = $request->user();
        $user->clickUpConnections()->create([
            'name' => $name,
            'api_token' => $token,
        ]);

        return redirect($frontendUrl.'?connected=1');
    }
}
```

- [ ] **Step 4: Add routes to web.php**

In `routes/web.php`, before the SPA catch-all route add:

```php
use App\Http\Controllers\MorningHub\ClickUpOAuthController;

// ClickUp OAuth — server-side redirect (OAuth state requires session)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('clickup/oauth/redirect', [ClickUpOAuthController::class, 'redirect'])
        ->middleware('throttle:5,1')
        ->name('clickup.oauth.redirect');
    Route::get('clickup/oauth/callback', [ClickUpOAuthController::class, 'callback'])
        ->name('clickup.oauth.callback');
});
```

- [ ] **Step 5: Run tests to verify they pass**

```bash
php artisan test --compact --filter=ClickUpOAuthTest
```

Expected: 8 tests PASS.

- [ ] **Step 6: Run PHPStan and Pint**

```bash
vendor/bin/phpstan analyse --error-format=table app/Http/Controllers/MorningHub/ClickUpOAuthController.php app/Services/ClickUpOAuthService.php
vendor/bin/pint --dirty --format agent
```

Expected: no errors.

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/MorningHub/ClickUpOAuthController.php app/Services/ClickUpOAuthService.php routes/web.php tests/Feature/MorningHub/ClickUpOAuthTest.php
git commit -m "feat: add ClickUpOAuthController and web routes"
```

---

### Task 3: Remove the manual store endpoint

**Files:**
- Delete: `app/Http/Requests/MorningHub/StoreClickUpConnectionRequest.php`
- Modify: `app/Http/Controllers/Api/ClickUpConnectionController.php`
- Modify: `routes/api.php`
- Modify: `tests/Feature/Api/ClickUpConnectionTest.php`

- [ ] **Step 1: Update the connection test — remove store tests**

Open `tests/Feature/Api/ClickUpConnectionTest.php`. Remove these two tests entirely:

```php
test('user can create a clickup connection', function () { ... });
test('user cannot create connection with invalid token', function () { ... });
```

- [ ] **Step 2: Run the remaining tests to verify they still pass**

```bash
php artisan test --compact --filter=ClickUpConnectionTest
```

Expected: remaining tests PASS.

- [ ] **Step 3: Remove the store route from api.php**

In `routes/api.php`, remove this line:

```php
Route::post('/morning-hub/clickup/connections', [ClickUpConnectionController::class, 'store'])->middleware('throttle:5,1');
```

- [ ] **Step 4: Remove the store method from ClickUpConnectionController**

In `app/Http/Controllers/Api/ClickUpConnectionController.php`:

Remove the `store` method entirely:

```php
public function store(StoreClickUpConnectionRequest $request): JsonResponse
{
    // ... remove this whole method
}
```

Remove the unused import at the top:

```php
use App\Http\Requests\MorningHub\StoreClickUpConnectionRequest;
```

- [ ] **Step 5: Delete StoreClickUpConnectionRequest**

```bash
rm app/Http/Requests/MorningHub/StoreClickUpConnectionRequest.php
```

- [ ] **Step 6: Run all tests**

```bash
php artisan test --compact
```

Expected: all tests PASS.

- [ ] **Step 7: Run PHPStan and Pint**

```bash
vendor/bin/phpstan analyse --error-format=table app/Http/Controllers/Api/ClickUpConnectionController.php
vendor/bin/pint --dirty --format agent
```

Expected: no errors.

- [ ] **Step 8: Commit**

```bash
git add app/Http/Controllers/Api/ClickUpConnectionController.php routes/api.php tests/Feature/Api/ClickUpConnectionTest.php
git rm app/Http/Requests/MorningHub/StoreClickUpConnectionRequest.php
git commit -m "feat: remove manual token store endpoint in favour of OAuth"
```

---

### Task 4: Clean up UpdateRequest and ClickUpConnection model

**Files:**
- Modify: `app/Http/Requests/MorningHub/UpdateClickUpConnectionRequest.php`
- Modify: `app/Http/Controllers/Api/ClickUpConnectionController.php`
- Modify: `app/Models/ClickUpConnection.php`

- [ ] **Step 1: Remove api_token from UpdateClickUpConnectionRequest**

In `app/Http/Requests/MorningHub/UpdateClickUpConnectionRequest.php`, remove these lines from `rules()`:

```php
'api_token' => ['sometimes', 'string', 'starts_with:pk_', 'min:10'],
```

- [ ] **Step 2: Remove api_token check from ClickUpConnectionController::update()**

In `app/Http/Controllers/Api/ClickUpConnectionController.php`, inside the `update` method, remove this block:

```php
if (isset($data['api_token'])) {
    $service = $this->clickUpServiceFactory->make($data['api_token']);
    if (! $service->testConnection()) {
        throw ValidationException::withMessages([
            'api_token' => ['The API token is invalid or the connection failed.'],
        ]);
    }
}
```

Also remove the now-unused `ValidationException` import:

```php
use Illuminate\Validation\ValidationException;
```

**Do NOT remove `ClickUpServiceFactory`** — it is still used by the `test` method (line ~86) which tests a live connection.

- [ ] **Step 3: Remove tokenFormatPattern from ClickUpConnection model**

In `app/Models/ClickUpConnection.php`, remove the method:

```php
public static function tokenFormatPattern(): ?string
{
    return '/^pk_.{6,}/';
}
```

Also remove the now-unused observer import if `ApiCredentialObserver` only used `tokenFormatPattern` — check `app/Observers/ApiCredentialObserver.php` first. If the observer still serves a purpose (e.g. other validation), leave it. If `tokenFormatPattern` was its only function and the observer is now empty, remove the `#[ObservedBy(ApiCredentialObserver::class)]` attribute and the import too.

- [ ] **Step 4: Run all tests**

```bash
php artisan test --compact
```

Expected: all tests PASS.

- [ ] **Step 5: Run PHPStan and Pint**

```bash
vendor/bin/phpstan analyse --error-format=table app/Http/Controllers/Api/ClickUpConnectionController.php app/Http/Requests/MorningHub/UpdateClickUpConnectionRequest.php app/Models/ClickUpConnection.php
vendor/bin/pint --dirty --format agent
```

Expected: no errors.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Requests/MorningHub/UpdateClickUpConnectionRequest.php app/Http/Controllers/Api/ClickUpConnectionController.php app/Models/ClickUpConnection.php
git commit -m "chore: remove manual token validation — connections created via OAuth only"
```

---

### Task 5: Frontend — ClickUpConnectionForm.vue

**Files:**
- Modify: `resources/js/components/morning-hub/ClickUpConnectionForm.vue`

Replace the form with a name-only input and an OAuth redirect button. On submit, navigate to the backend redirect route via `window.location.href`.

- [ ] **Step 1: Replace the component script**

Replace the entire `<script setup lang="ts">` block with:

```typescript
<script setup lang="ts">
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import { useTranslations } from '@/composables/useTranslations';
import type { ClickUpConnection } from '@/types';
import axiosInstance from '@/lib/axios';

const { t } = useTranslations();

const props = defineProps<{
    connection?: ClickUpConnection;
}>();

const emit = defineEmits<{ success: [] }>();

const isOpen = defineModel<boolean>('open', { default: false });

const connName = ref(props.connection?.name ?? '');
const processing = ref(false);
const errors = ref<Record<string, string>>({});

function connectWithOAuth() {
    const name = connName.value.trim() || 'ClickUp';
    window.location.href = `/clickup/oauth/redirect?name=${encodeURIComponent(name)}`;
}

async function submitUpdate() {
    processing.value = true;
    errors.value = {};

    try {
        await axiosInstance.put(
            `/morning-hub/clickup/connections/${props.connection!.id}`,
            { name: connName.value },
        );
        emit('success');
        isOpen.value = false;
    } catch (err: unknown) {
        const axiosErr = err as {
            response?: { data?: { errors?: Record<string, string[]> } };
        };
        if (axiosErr.response?.data?.errors) {
            const rawErrors = axiosErr.response.data.errors;
            errors.value = Object.fromEntries(
                Object.entries(rawErrors).map(([k, v]) => [k, v[0]]),
            );
        }
    } finally {
        processing.value = false;
    }
}

function submit() {
    if (props.connection) {
        submitUpdate();
    } else {
        connectWithOAuth();
    }
}
</script>
```

- [ ] **Step 2: Replace the template**

Replace the entire `<template>` block with:

```html
<template>
    <Dialog v-model:open="isOpen">
        <DialogContent>
            <form class="space-y-6" @submit.prevent="submit">
                <DialogHeader>
                    <DialogTitle>{{
                        connection
                            ? t('Edytuj połączenie')
                            : t('Dodaj połączenie')
                    }}</DialogTitle>
                    <DialogDescription>
                        {{
                            connection
                                ? t('Zaktualizuj połączenie ClickUp.')
                                : t(
                                      'Połącz workspace ClickUp przez OAuth.',
                                  )
                        }}
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-4">
                    <div class="grid gap-2">
                        <Label for="conn-name">{{ t('Nazwa') }}</Label>
                        <Input
                            id="conn-name"
                            v-model="connName"
                            required
                            :placeholder="t('np. Praca, Osobiste')"
                        />
                        <InputError :message="errors.name" />
                    </div>
                </div>

                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button variant="secondary">{{ t('Anuluj') }}</Button>
                    </DialogClose>
                    <Button type="submit" :disabled="processing">
                        {{
                            connection
                                ? t('Zapisz')
                                : t('Połącz z ClickUp')
                        }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
```

- [ ] **Step 3: Build frontend and verify no TypeScript errors**

```bash
npm run build 2>&1 | tail -15
```

Expected: build succeeds, no TypeScript errors.

- [ ] **Step 4: Commit**

```bash
git add resources/js/components/morning-hub/ClickUpConnectionForm.vue
git commit -m "feat: replace token form with OAuth connect button in ClickUpConnectionForm"
```

---

### Task 6: Frontend — ClickUp.vue — handle OAuth redirect result

**Files:**
- Modify: `resources/js/pages/morning-hub/ClickUp.vue`

After the OAuth flow, the backend redirects to `/morning-hub/clickup?connected=1` or `?error=...`. The page must read these params on mount and show a toast.

- [ ] **Step 1: Add imports to the script block**

In `resources/js/pages/morning-hub/ClickUp.vue`, add these imports to the `<script setup>` block:

```typescript
import { useRoute, useRouter } from 'vue-router';
import { toast } from 'vue-sonner';
```

- [ ] **Step 2: Add route and router refs**

After the existing `const { t } = useTranslations();` line, add:

```typescript
const route = useRoute();
const router = useRouter();
```

- [ ] **Step 3: Update onMounted to handle OAuth result**

Replace the existing `onMounted(loadConnections);` with:

```typescript
onMounted(async () => {
    await loadConnections();

    const connected = route.query.connected;
    const error = route.query.error;

    if (connected) {
        toast.success(t('Połączono z ClickUp.'));
    } else if (error === 'no_code') {
        toast.error(t('Anulowano autoryzację ClickUp.'));
    } else if (error === 'invalid_state') {
        toast.error(t('Błąd bezpieczeństwa. Spróbuj ponownie.'));
    } else if (error === 'auth_failed') {
        toast.error(t('Autoryzacja ClickUp nie powiodła się.'));
    }

    if (connected || error) {
        await router.replace({ query: {} });
    }
});
```

- [ ] **Step 4: Build frontend and verify no TypeScript errors**

```bash
npm run build 2>&1 | tail -15
```

Expected: build succeeds.

- [ ] **Step 5: Commit**

```bash
git add resources/js/pages/morning-hub/ClickUp.vue
git commit -m "feat: show OAuth result toast on ClickUp connections page"
```

---

## Final verification

- [ ] **Run the full test suite**

```bash
php artisan test --compact
```

Expected: all tests PASS, no failures.

- [ ] **Run PHPStan on all modified PHP files**

```bash
vendor/bin/phpstan analyse --error-format=table app/
```

Expected: no errors.
