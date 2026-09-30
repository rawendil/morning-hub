# GA4 → Umami Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Zastąpić GA4 i baner cookies analityką Umami (bez cookies), serwowaną przez domenę Morning Hub.

**Architecture:** `UmamiProxyService` pobiera skrypt trackera z instancji Umami (cache 24 h) i przekazuje zdarzenia na jej `/api/send`. Dwa cienkie kontrolery wystawiają go jako `GET /api/mh.js` i `POST /api/send` w `routes/api.php` (rejestrowanym przed catch-allem SPA). `spa.blade.php` renderuje tag trackera wskazujący wyłącznie na naszą domenę; frontendowy mechanizm zgody jest usuwany.

**Tech Stack:** Laravel 12, Pest 4 (`Http::fake`), Blade, Vue 3 + TypeScript, Vitest.

**Spec:** `docs/superpowers/specs/2026-09-30-umami-analytics-design.md`

## Global Constraints

- Config: `services.umami.url` ← `UMAMI_URL`, `services.umami.website_id` ← `UMAMI_WEBSITE_ID`; oba puste = analityka wyłączona.
- Adres instancji Umami (`UMAMI_URL`) **nigdy** nie trafia do HTML.
- Trasy: `GET /api/mh.js`, `POST /api/send` (`throttle:120,1`) — obie w `routes/api.php`, nigdy w `web.php` ani `withRouting(then:)`.
- Tag: `defer`, `src="{{ url('/api/mh.js') }}"`, `data-website-id`, `data-host-url="{{ url('/') }}"`, `data-exclude-search="true"`, `data-do-not-track="true"`.
- Cache skryptu: klucz `umami:tracker-script`, 24 h; przeglądarka `max-age=86400`, po awarii pusty body i `max-age=60`.
- Timeouty: skrypt `connectTimeout(3)->timeout(5)`, zdarzenie `connectTimeout(2)->timeout(2)`.
- Nagłówki forwardu: `X-Client-IP` (= `$request->ip()`), `User-Agent`, `Accept-Language`.
- Beacon odpowiada zawsze `204` (poza 404 przy wyłączonej analityce i 429 z throttle).
- Brak ręcznego `page_view` w Vue Router.
- Kontrolery bez logiki biznesowej i bez metod prywatnych (`.ai/guidelines/architecture.md`).
- Commity: `typ: opis` po angielsku, bez zakresu; stage wyłącznie własnych plików, nigdy `git add -A`. Gałąź `development`.

## Review Focus

1. Catch-all SPA `/{any}` łapiący `/api/mh.js` — oczekiwane: JS, nie HTML (test w Task 2).
2. Token resetu hasła w query (`/reset-password?token=…`) — oczekiwane: tag ma `data-exclude-search="true"` (test w Task 3).
3. Awaria instancji przy pierwszym pobraniu skryptu — oczekiwane: pusty skrypt teraz, ponowna próba przy następnym żądaniu (bez zatrutego cache'u) (test w Task 1).
4. Ruch zza proxy MyDevil — oczekiwane: `X-Client-IP` = IP czytelnika z `X-Forwarded-For`, nie serwera (test w Task 2).
5. Odpowiedzi proxy z `Set-Cookie` — oczekiwane: brak cookies na obu trasach, żeby analityka pozostała cookieless (test w Task 2).

---

## File Structure

| Plik | Rola |
| --- | --- |
| `app/Services/UmamiProxyService.php` (nowy) | config, pobranie i cache skryptu, forward zdarzeń |
| `app/Http/Controllers/Api/UmamiScriptController.php` (nowy) | `GET /api/mh.js` |
| `app/Http/Controllers/Api/UmamiCollectController.php` (nowy) | `POST /api/send` |
| `routes/api.php` | rejestracja dwóch tras |
| `config/services.php`, `.env.example` | klucze Umami zamiast GA |
| `resources/views/spa.blade.php` | tag trackera zamiast gtag |
| `resources/views/app.blade.php` | usunięty (martwy) |
| `resources/js/{App.vue,components/AppSidebar.vue,types/global.d.ts}` | usunięcie zgody i typów gtag |
| `resources/js/components/CookieConsentModal.vue`, `resources/js/composables/useCookieConsent.ts` | usunięte |
| `lang/en.json` | usunięte klucze modalu |
| `resources/views/privacy-policy.blade.php` | §4, §5, §11 pod Umami |
| `.ai/guidelines/analytics.md` (nowy) | reguła dla agentów |
| `tests/Feature/Services/UmamiProxyServiceTest.php`, `tests/Feature/Api/UmamiProxyRoutesTest.php`, `tests/Feature/UmamiScriptTagTest.php` (nowe), `tests/Feature/PrivacyPolicyTest.php` | testy |

---

### Task 1: `UmamiProxyService` + konfiguracja

**Files:**
- Create: `app/Services/UmamiProxyService.php`
- Modify: `config/services.php:40-43` (blok `google`), `.env.example:89`
- Test: `tests/Feature/Services/UmamiProxyServiceTest.php`

**Interfaces:**
- Produces:
  - `UmamiProxyService::isEnabled(): bool`
  - `UmamiProxyService::websiteId(): ?string`
  - `UmamiProxyService::trackerScript(): ?string`
  - `UmamiProxyService::forwardEvent(string $payload, string $clientIp, string $userAgent, string $acceptLanguage): void`

- [ ] **Step 1: Utwórz klasę i test**

```bash
php artisan make:class Services/UmamiProxyService --no-interaction
php artisan make:test --pest Services/UmamiProxyServiceTest --no-interaction
```

- [ ] **Step 2: Napisz failing test** — `tests/Feature/Services/UmamiProxyServiceTest.php`:

```php
<?php

use App\Services\UmamiProxyService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

const UMAMI_UPSTREAM = 'https://umami.example.test';
const UMAMI_WEBSITE_ID = 'ffffffff-0000-4000-8000-000000000001';

beforeEach(function () {
    config([
        'services.umami.url' => UMAMI_UPSTREAM,
        'services.umami.website_id' => UMAMI_WEBSITE_ID,
    ]);
});

test('is enabled only when both the upstream and the website id are configured', function (?string $url, ?string $websiteId, bool $expected) {
    config(['services.umami.url' => $url, 'services.umami.website_id' => $websiteId]);

    expect(app(UmamiProxyService::class)->isEnabled())->toBe($expected);
})->with([
    'both set' => [UMAMI_UPSTREAM, UMAMI_WEBSITE_ID, true],
    'url missing' => [null, UMAMI_WEBSITE_ID, false],
    'website id missing' => [UMAMI_UPSTREAM, null, false],
    'blank values' => ['  ', '  ', false],
]);

test('exposes the trimmed website id', function () {
    config(['services.umami.website_id' => '  '.UMAMI_WEBSITE_ID.'  ']);

    expect(app(UmamiProxyService::class)->websiteId())->toBe(UMAMI_WEBSITE_ID);
});

test('fetches the tracker script once and serves it from cache afterwards', function () {
    Http::fake([UMAMI_UPSTREAM.'/script.js' => Http::response('window.umami=1;')]);

    $service = app(UmamiProxyService::class);

    expect($service->trackerScript())->toBe('window.umami=1;')
        ->and($service->trackerScript())->toBe('window.umami=1;');

    Http::assertSentCount(1);
});

test('does not double the slash when the upstream ends with one', function () {
    config(['services.umami.url' => UMAMI_UPSTREAM.'/']);
    Http::fake([UMAMI_UPSTREAM.'/script.js' => Http::response('window.umami=1;')]);

    app(UmamiProxyService::class)->trackerScript();

    Http::assertSent(fn (Request $request) => $request->url() === UMAMI_UPSTREAM.'/script.js');
});

test('returns null and caches nothing when the upstream fails', function () {
    Http::fake([UMAMI_UPSTREAM.'/script.js' => Http::response('boom', 500)]);

    expect(app(UmamiProxyService::class)->trackerScript())->toBeNull()
        ->and(Cache::has('umami:tracker-script'))->toBeFalse();
});

test('retries the upstream on the next call after a failure', function () {
    Http::fakeSequence(UMAMI_UPSTREAM.'/script.js')
        ->push('boom', 500)
        ->push('window.umami=1;');

    $service = app(UmamiProxyService::class);

    expect($service->trackerScript())->toBeNull()
        ->and($service->trackerScript())->toBe('window.umami=1;');
});

test('returns null when the upstream is unreachable', function () {
    Http::fake(fn () => throw new ConnectionException('timeout'));

    expect(app(UmamiProxyService::class)->trackerScript())->toBeNull();
});

test('forwards the event payload untouched with the reader headers', function () {
    Http::fake([UMAMI_UPSTREAM.'/api/send' => Http::response(['ok' => true])]);

    app(UmamiProxyService::class)->forwardEvent(
        '{"type":"event","payload":{"url":"/dashboard"}}',
        '203.0.113.7',
        'Mozilla/5.0 Test',
        'pl-PL,pl;q=0.9',
    );

    Http::assertSent(fn (Request $request) => $request->url() === UMAMI_UPSTREAM.'/api/send'
        && $request->method() === 'POST'
        && $request->body() === '{"type":"event","payload":{"url":"/dashboard"}}'
        && $request->header('X-Client-IP') === ['203.0.113.7']
        && $request->header('User-Agent') === ['Mozilla/5.0 Test']
        && $request->header('Accept-Language') === ['pl-PL,pl;q=0.9']
        && $request->header('Content-Type') === ['application/json']);
});

test('swallows upstream exceptions when forwarding an event', function () {
    Http::fake(fn () => throw new ConnectionException('timeout'));

    app(UmamiProxyService::class)->forwardEvent('{}', '203.0.113.7', 'UA', 'pl');
})->throwsNoExceptions();
```

- [ ] **Step 3: Uruchom — ma nie przejść**

Run: `php artisan test --compact tests/Feature/Services/UmamiProxyServiceTest.php`
Expected: FAIL (`Call to undefined method App\Services\UmamiProxyService::isEnabled()`).

- [ ] **Step 4: Konfiguracja** — w `config/services.php` zamień w bloku `google` linię `'analytics_id' => env('VITE_GA_MEASUREMENT_ID'),` na nic (usuń ją) i dodaj nowy blok na końcu tablicy:

```php
    'umami' => [
        'url' => env('UMAMI_URL'),
        'website_id' => env('UMAMI_WEBSITE_ID'),
    ],
```

W `.env.example` zamień linię `VITE_GA_MEASUREMENT_ID=` na:

```dotenv
# Umami (self-hosted, bez cookies). UMAMI_URL to adres instancji — nigdy nie trafia
# do HTML: skrypt i beacon idą przez /api/mh.js i /api/send. Puste = brak analityki.
UMAMI_URL=
UMAMI_WEBSITE_ID=
```

- [ ] **Step 5: Implementacja** — `app/Services/UmamiProxyService.php`:

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Transport between the browser and the self-hosted Umami instance. The instance
 * address stays server-side: the tracker script and its events travel through the
 * Morning Hub domain, so neither ad blockers nor the page source see the upstream.
 */
class UmamiProxyService
{
    private const SCRIPT_CACHE_KEY = 'umami:tracker-script';

    private const SCRIPT_CACHE_HOURS = 24;

    public function isEnabled(): bool
    {
        return $this->upstream() !== null && $this->websiteId() !== null;
    }

    public function websiteId(): ?string
    {
        $websiteId = trim((string) config('services.umami.website_id'));

        return $websiteId !== '' ? $websiteId : null;
    }

    /**
     * The tracker script, cached for a day. A failed fetch returns null and is not
     * cached, so the next request retries the upstream.
     */
    public function trackerScript(): ?string
    {
        $cached = Cache::get(self::SCRIPT_CACHE_KEY);

        if (is_string($cached)) {
            return $cached;
        }

        $script = $this->fetchTrackerScript();

        if ($script !== null) {
            Cache::put(self::SCRIPT_CACHE_KEY, $script, now()->addHours(self::SCRIPT_CACHE_HOURS));
        }

        return $script;
    }

    /**
     * Passes a tracker event to the upstream untouched. Without the reader's IP,
     * user agent and language the upstream would profile this server instead.
     * Failures are swallowed: a lost page view is cheaper than a broken beacon.
     */
    public function forwardEvent(string $payload, string $clientIp, string $userAgent, string $acceptLanguage): void
    {
        $upstream = $this->upstream();

        if ($upstream === null) {
            return;
        }

        try {
            Http::withHeaders([
                'X-Client-IP' => $clientIp,
                'User-Agent' => $userAgent,
                'Accept-Language' => $acceptLanguage,
            ])
                ->connectTimeout(2)
                ->timeout(2)
                ->withBody($payload, 'application/json')
                ->post($upstream.'/api/send');
        } catch (Throwable) {
            return;
        }
    }

    private function fetchTrackerScript(): ?string
    {
        $upstream = $this->upstream();

        if ($upstream === null) {
            return null;
        }

        try {
            $response = Http::connectTimeout(3)->timeout(5)->get($upstream.'/script.js');
        } catch (Throwable) {
            return null;
        }

        return $response->successful() ? $response->body() : null;
    }

    private function upstream(): ?string
    {
        $url = rtrim(trim((string) config('services.umami.url')), '/');

        return $url !== '' ? $url : null;
    }
}
```

- [ ] **Step 6: Uruchom — ma przejść**

Run: `php artisan test --compact tests/Feature/Services/UmamiProxyServiceTest.php`
Expected: PASS (12 testów, w tym 4 przypadki datasetu).

- [ ] **Step 7: Pint + PHPStan**

```bash
vendor/bin/pint --dirty --format agent
vendor/bin/phpstan analyse --error-format=table app/Services/UmamiProxyService.php
```

Expected: brak błędów.

- [ ] **Step 8: Commit**

```bash
git add app/Services/UmamiProxyService.php config/services.php .env.example tests/Feature/Services/UmamiProxyServiceTest.php
git commit -m "feat: add Umami proxy service for the tracker script and events"
```

---

### Task 2: Trasy proxy `GET /api/mh.js` i `POST /api/send`

**Files:**
- Create: `app/Http/Controllers/Api/UmamiScriptController.php`, `app/Http/Controllers/Api/UmamiCollectController.php`
- Modify: `routes/api.php` (importy + trasy na początku pliku, przed `/auth/login`)
- Test: `tests/Feature/Api/UmamiProxyRoutesTest.php`

**Interfaces:**
- Consumes: `UmamiProxyService::isEnabled()`, `trackerScript()`, `forwardEvent(...)` z Task 1.
- Produces: trasy nazwane `analytics.script` (`/api/mh.js`) i `analytics.collect` (`/api/send`).

- [ ] **Step 1: Utwórz kontrolery i test**

```bash
php artisan make:controller Api/UmamiScriptController --invokable --no-interaction
php artisan make:controller Api/UmamiCollectController --invokable --no-interaction
php artisan make:test --pest Api/UmamiProxyRoutesTest --no-interaction
```

- [ ] **Step 2: Napisz failing test** — `tests/Feature/Api/UmamiProxyRoutesTest.php`:

```php
<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

const PROXY_UPSTREAM = 'https://umami.example.test';

beforeEach(function () {
    config([
        'services.umami.url' => PROXY_UPSTREAM,
        'services.umami.website_id' => 'ffffffff-0000-4000-8000-000000000001',
    ]);
});

test('serves the tracker script as cacheable javascript', function () {
    Http::fake([PROXY_UPSTREAM.'/script.js' => Http::response('window.umami=1;')]);

    $response = $this->get('/api/mh.js');

    $response->assertOk();
    expect($response->getContent())->toBe('window.umami=1;')
        ->and($response->headers->get('Content-Type'))->toBe('text/javascript; charset=UTF-8')
        ->and($response->headers->get('Cache-Control'))->toContain('max-age=86400')
        ->and($response->headers->get('Cache-Control'))->toContain('public');
});

test('serves an empty script with a short cache when the upstream is down', function () {
    Http::fake([PROXY_UPSTREAM.'/script.js' => Http::response('boom', 500)]);

    $response = $this->get('/api/mh.js');

    $response->assertOk();
    expect($response->getContent())->toBe('')
        ->and($response->headers->get('Cache-Control'))->toContain('max-age=60');
});

test('is not swallowed by the SPA catch-all route', function () {
    Http::fake([PROXY_UPSTREAM.'/script.js' => Http::response('window.umami=1;')]);

    expect($this->get('/api/mh.js')->getContent())->not->toContain('<!DOCTYPE html>');
});

test('forwards the beacon and always answers 204', function () {
    Http::fake([PROXY_UPSTREAM.'/api/send' => Http::response('boom', 500)]);

    $this->call('POST', '/api/send', [], [], [], ['CONTENT_TYPE' => 'application/json'], '{"type":"event"}')
        ->assertNoContent();

    Http::assertSent(fn (Request $request) => $request->url() === PROXY_UPSTREAM.'/api/send'
        && $request->body() === '{"type":"event"}');
});

test('forwards the reader ip from behind the hosting proxy', function () {
    Http::fake([PROXY_UPSTREAM.'/api/send' => Http::response()]);

    $this->withHeaders([
        'X-Forwarded-For' => '203.0.113.7',
        'User-Agent' => 'Mozilla/5.0 Test',
        'Accept-Language' => 'pl-PL',
    ])->call('POST', '/api/send', [], [], [], ['CONTENT_TYPE' => 'application/json'], '{}');

    Http::assertSent(fn (Request $request) => $request->header('X-Client-IP') === ['203.0.113.7']
        && $request->header('User-Agent') === ['Mozilla/5.0 Test']
        && $request->header('Accept-Language') === ['pl-PL']);
});

test('sets no cookies on either proxy route', function () {
    Http::fake([
        PROXY_UPSTREAM.'/script.js' => Http::response('window.umami=1;'),
        PROXY_UPSTREAM.'/api/send' => Http::response(),
    ]);

    expect($this->get('/api/mh.js')->headers->getCookies())->toBeEmpty()
        ->and($this->postJson('/api/send', [])->headers->getCookies())->toBeEmpty();
});

test('answers 404 on both routes when analytics is switched off', function () {
    config(['services.umami.url' => null]);
    Http::fake();

    $this->get('/api/mh.js')->assertNotFound();
    $this->postJson('/api/send', [])->assertNotFound();

    Http::assertNothingSent();
});

test('throttles the beacon', function () {
    Http::fake([PROXY_UPSTREAM.'/api/send' => Http::response()]);

    foreach (range(1, 120) as $attempt) {
        $this->postJson('/api/send', [])->assertNoContent();
    }

    $this->postJson('/api/send', [])->assertTooManyRequests();
});
```

- [ ] **Step 3: Uruchom — ma nie przejść**

Run: `php artisan test --compact tests/Feature/Api/UmamiProxyRoutesTest.php`
Expected: FAIL — `/api/mh.js` zwraca HTML SPA / `/api/send` zwraca 405 lub 404.

- [ ] **Step 4: Kontrolery**

`app/Http/Controllers/Api/UmamiScriptController.php`:

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\UmamiProxyService;
use Illuminate\Http\Response;

class UmamiScriptController extends Controller
{
    public function __construct(private UmamiProxyService $umami) {}

    public function __invoke(): Response
    {
        abort_unless($this->umami->isEnabled(), 404);

        $script = $this->umami->trackerScript();

        return response($script ?? '', 200, [
            'Content-Type' => 'text/javascript; charset=UTF-8',
            'Cache-Control' => 'public, max-age='.($script === null ? 60 : 86400),
        ]);
    }
}
```

`app/Http/Controllers/Api/UmamiCollectController.php`:

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\UmamiProxyService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class UmamiCollectController extends Controller
{
    public function __construct(private UmamiProxyService $umami) {}

    public function __invoke(Request $request): Response
    {
        abort_unless($this->umami->isEnabled(), 404);

        $this->umami->forwardEvent(
            $request->getContent(),
            (string) $request->ip(),
            (string) $request->userAgent(),
            (string) $request->header('Accept-Language'),
        );

        return response()->noContent();
    }
}
```

- [ ] **Step 5: Trasy** — w `routes/api.php` dodaj importy (alfabetycznie w bloku `use`):

```php
use App\Http\Controllers\Api\UmamiCollectController;
use App\Http\Controllers\Api\UmamiScriptController;
```

i przed `Route::post('/auth/login', …)`:

```php
// Umami analytics proxy. Both routes must stay in api.php: it is registered before
// web.php, whose SPA catch-all would otherwise answer /api/mh.js with HTML. The api
// group adds no session or CSRF, so the proxy stays cookieless. The /api/send path
// is fixed by the tracker (`${data-host-url}/api/send`).
Route::get('/mh.js', UmamiScriptController::class)->name('analytics.script');
Route::post('/send', UmamiCollectController::class)->middleware('throttle:120,1')->name('analytics.collect');
```

- [ ] **Step 6: Uruchom — ma przejść**

Run: `php artisan test --compact tests/Feature/Api/UmamiProxyRoutesTest.php`
Expected: PASS (8 testów).

- [ ] **Step 7: Pint + PHPStan**

```bash
vendor/bin/pint --dirty --format agent
vendor/bin/phpstan analyse --error-format=table app/Http/Controllers/Api/UmamiScriptController.php app/Http/Controllers/Api/UmamiCollectController.php
```

- [ ] **Step 8: Commit**

```bash
git add app/Http/Controllers/Api/UmamiScriptController.php app/Http/Controllers/Api/UmamiCollectController.php routes/api.php tests/Feature/Api/UmamiProxyRoutesTest.php
git commit -m "feat: proxy the Umami tracker and beacon through the app domain"
```

---

### Task 3: Tag trackera zamiast GA w `spa.blade.php`

**Files:**
- Modify: `resources/views/spa.blade.php:33-48`
- Delete: `resources/views/app.blade.php`
- Test: `tests/Feature/UmamiScriptTagTest.php`

**Interfaces:**
- Consumes: `UmamiProxyService::isEnabled()`, `websiteId()` z Task 1.

- [ ] **Step 1: Utwórz test**

```bash
php artisan make:test --pest UmamiScriptTagTest --no-interaction
```

- [ ] **Step 2: Napisz failing test** — `tests/Feature/UmamiScriptTagTest.php`:

```php
<?php

beforeEach(function () {
    config([
        'services.umami.url' => 'https://umami-upstream.example.test',
        'services.umami.website_id' => 'ffffffff-0000-4000-8000-000000000001',
    ]);
});

test('renders the tracker tag pointing at the app domain', function () {
    $content = $this->get('/')->assertOk()->getContent();

    expect($content)
        ->toMatch('/<script\s+defer\s+src="'.preg_quote(url('/api/mh.js'), '/').'"/')
        ->toContain('data-website-id="ffffffff-0000-4000-8000-000000000001"')
        ->toContain('data-host-url="'.url('/').'"')
        ->toContain('data-do-not-track="true"');
});

test('strips query strings so reset tokens never reach analytics', function () {
    expect($this->get('/reset-password?token=secret&email=a@b.test')->getContent())
        ->toContain('data-exclude-search="true"');
});

test('never exposes the upstream instance address', function () {
    expect($this->get('/')->getContent())->not->toContain('umami-upstream.example.test');
});

test('renders no tracker when analytics is switched off', function () {
    config(['services.umami.website_id' => null]);

    expect($this->get('/')->getContent())
        ->not->toContain('/api/mh.js')
        ->not->toContain('data-website-id');
});

test('leaves no Google Analytics code behind', function () {
    expect($this->get('/')->getContent())
        ->not->toContain('googletagmanager.com')
        ->not->toContain('gtag(')
        ->not->toContain('dataLayer');
});
```

- [ ] **Step 3: Uruchom — ma nie przejść**

Run: `php artisan test --compact tests/Feature/UmamiScriptTagTest.php`
Expected: FAIL (brak tagu `/api/mh.js`).

- [ ] **Step 4: Implementacja** — w `resources/views/spa.blade.php` zastąp cały blok `@if(config('services.google.analytics_id')) … @endif` (linie 33-48):

```blade
        @inject('umami', 'App\Services\UmamiProxyService')
        @if ($umami->isEnabled())
            {{-- Umami: no cookies, served through our domain (see UmamiProxyService). Page views
                 of Vue Router come from the tracker's own history.pushState hook — do not send them
                 manually. data-exclude-search keeps reset-password tokens out of the statistics. --}}
            <script
                defer
                src="{{ url('/api/mh.js') }}"
                data-website-id="{{ $umami->websiteId() }}"
                data-host-url="{{ url('/') }}"
                data-exclude-search="true"
                data-do-not-track="true"
            ></script>
        @endif
```

Usuń martwy szablon:

```bash
git rm resources/views/app.blade.php
```

- [ ] **Step 5: Uruchom — ma przejść**

Run: `php artisan test --compact tests/Feature/UmamiScriptTagTest.php tests/Feature/OpenGraphMetaTest.php`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add resources/views/spa.blade.php tests/Feature/UmamiScriptTagTest.php
git commit -m "feat: replace the Google Analytics snippet with the Umami tracker"
```

---

### Task 4: Usunięcie baneru zgody z frontendu

**Files:**
- Delete: `resources/js/components/CookieConsentModal.vue`, `resources/js/composables/useCookieConsent.ts`
- Modify: `resources/js/App.vue`, `resources/js/components/AppSidebar.vue:6,26,31,99-104`, `resources/js/types/global.d.ts:3-8`, `lang/en.json:12,151,166,256,275`

Brak nowego testu jednostkowego — usuwamy kod; dowodem są `types:check`, `lint:check` i Vitest (nic nie importuje usuniętych plików) oraz test z Task 3 (`dataLayer` nieobecny).

- [ ] **Step 1: Usuń pliki**

```bash
git rm resources/js/components/CookieConsentModal.vue resources/js/composables/useCookieConsent.ts
```

- [ ] **Step 2: `App.vue`** — całość pliku:

```vue
<template>
    <RouterView />
    <Toaster position="bottom-right" :rich-colors="true" />
</template>

<script setup lang="ts">
import { Toaster } from 'vue-sonner';
</script>
```

- [ ] **Step 3: `AppSidebar.vue`** — usuń: `Cookie,` z importu `lucide-vue-next` (linia 6), `import { useCookieConsent } from '@/composables/useCookieConsent';` (26), `const { openSettings } = useCookieConsent();` (31) oraz cały element:

```vue
                <SidebarMenuItem>
                    <SidebarMenuButton @click="openSettings">
                        <Cookie />
                        <span>{{ t('Ustawienia cookies') }}</span>
                    </SidebarMenuButton>
                </SidebarMenuItem>
```

Jeśli po usunięciu `t` przestanie być używane w pliku — sprawdź (`grep -n "t(" resources/js/components/AppSidebar.vue`); jest używane w `NavMain :label`, więc zostaje.

- [ ] **Step 4: `types/global.d.ts`** — usuń blok `declare global { interface Window { gtag…; dataLayer…; } }`, zostawiając `export {};` i deklarację `vite/client`.

- [ ] **Step 5: `lang/en.json`** — usuń klucze: `"Akceptuj"`, `"Odrzuć"`, `"Pliki cookies"`, `"Ustawienia cookies"`, `"Używamy plików cookies do analizy ruchu…"`. Najpierw potwierdź, że nigdzie indziej ich nie ma:

```bash
for k in "Akceptuj" "Odrzuć" "Pliki cookies" "Ustawienia cookies" "Używamy plików cookies"; do grep -rn --include=*.vue --include=*.ts --include=*.php "$k" resources app; done
```

Expected: brak wyników (po krokach 1-3).

- [ ] **Step 6: Weryfikacja frontu**

```bash
npm run types:check && npm run lint:check && npm run format:check && npm run test
```

Expected: wszystko zielone.

- [ ] **Step 7: Commit**

```bash
git add resources/js/App.vue resources/js/components/AppSidebar.vue resources/js/types/global.d.ts lang/en.json
git commit -m "feat: drop the cookie consent banner now that analytics is cookieless"
```

---

### Task 5: Polityka prywatności i reguła dla agentów

**Files:**
- Modify: `resources/views/privacy-policy.blade.php:132,141-144,193`
- Create: `.ai/guidelines/analytics.md`
- Test: `tests/Feature/PrivacyPolicyTest.php`

- [ ] **Step 1: Failing test** — dopisz do `tests/Feature/PrivacyPolicyTest.php`:

```php
test('privacy policy describes cookieless Umami analytics instead of Google Analytics', function () {
    $this->get('/privacy-policy')
        ->assertOk()
        ->assertSee('Umami')
        ->assertSee('umami.disabled')
        ->assertDontSee('Google Analytics')
        ->assertDontSee('Google Tag Manager')
        ->assertDontSee('gaoptout', false);
});

test('privacy policy states that Do Not Track is honoured', function () {
    $this->get('/privacy-policy')
        ->assertOk()
        ->assertDontSee('nie reagujemy na te sygnały')
        ->assertSee('respektujemy');
});
```

Run: `php artisan test --compact tests/Feature/PrivacyPolicyTest.php`
Expected: FAIL.

- [ ] **Step 2: §4** — linia 132:

```html
  <li><strong>Analityka webowa:</strong> Umami (własna instancja, bez plików cookie)</li>
```

- [ ] **Step 3: §5** — zamień „Krótko" (141), akapit „Zezwalamy również podmiotom trzecim…" (143) i akapit „Google Analytics" (144) na:

```html
<p><em>Krótko: Używamy wyłącznie niezbędnych plików cookie. Statystyki odwiedzin prowadzimy bez plików cookie.</em></p>
```

(akapit 142 o cookies niezbędnych zostaje) oraz po nim:

```html
<p><strong>Umami.</strong> Statystyki odwiedzin zbieramy narzędziem Umami uruchomionym na własnym serwerze w Unii Europejskiej. Narzędzie nie zapisuje na Twoim urządzeniu plików cookie ani identyfikatorów i nie pozwala rozpoznać Cię przy kolejnej wizycie. Skrypt i zdarzenia obsługuje domena Morning Hub, która przekazuje je do instancji analitycznej wraz z adresem IP, typem przeglądarki i językiem — adres IP służy wyłącznie do wyznaczenia kraju wizyty i nie jest przechowywany. Adresy odwiedzanych stron zapisujemy bez parametrów zapytania. Podstawą przetwarzania jest nasz prawnie uzasadniony interes (art. 6 ust. 1 lit. f RODO) polegający na mierzeniu, jak korzystasz z Usług. Możesz wyłączyć zliczanie, włączając w przeglądarce sygnał „Do Not Track" albo ustawiając w pamięci lokalnej przeglądarki klucz <code>umami.disabled</code> na wartość <code>1</code>.</p>
```

- [ ] **Step 4: §11** — zamień treść akapitu (193):

```html
<p>Większość przeglądarek internetowych zawiera funkcję „Do-Not-Track" (DNT). Respektujemy ten sygnał w statystykach odwiedzin: gdy Twoja przeglądarka go wysyła, Umami nie rejestruje Twoich wizyt.</p>
```

- [ ] **Step 5: Uruchom — ma przejść**

Run: `php artisan test --compact tests/Feature/PrivacyPolicyTest.php`
Expected: PASS.

- [ ] **Step 6: Reguła** — `.ai/guidelines/analytics.md`:

```markdown
# Analytics

## Umami bez cookies — nie przywracaj GA ani baneru zgody

Statystyki prowadzi self-hosted Umami (`UMAMI_URL` + `UMAMI_WEBSITE_ID` → `config('services.umami')`), obsługiwane przez `App\Services\UmamiProxyService`. Google Analytics, Consent Mode, `window.gtag` i baner cookies zostały usunięte świadomie. Umami nie zapisuje cookies ani identyfikatorów, więc podstawą jest art. 6 ust. 1 lit. f RODO, nie zgoda. Zmiana analityki wymaga poprawienia polityki prywatności (§4, §5, §11).

- **Adres instancji nie trafia do HTML.** Przeglądarka rozmawia tylko z `/api/mh.js` i `/api/send`.
- **Trasy proxy zostają w `routes/api.php`.** `api.php` rejestruje się przed `web.php`, którego catch-all SPA `/{any}` przechwyciłby każdą późniejszą trasę. Grupa `web` dokleiłaby też cookies sesji i CSRF.
- **Nie dopisuj ręcznego `page_view` w Vue Router.** Tracker sam podpina się pod `history.pushState`; ręczne wywołanie zdubluje odsłony.
- **`data-exclude-search="true"` jest obowiązkowy** — `/reset-password?token=…&email=…` wysłałby token i e-mail do statystyk.
- Lokalnie i na stagingu `UMAMI_*` zostają puste, żeby ruch dev nie trafiał do statystyk produkcji.
```

- [ ] **Step 7: Commit**

```bash
git add resources/views/privacy-policy.blade.php tests/Feature/PrivacyPolicyTest.php .ai/guidelines/analytics.md
git commit -m "docs: describe Umami analytics in the privacy policy and agent rules"
```

---

### Task 6: Brama jakości i wdrożenie

- [ ] **Step 1: Pełna brama**

Run: `composer ci`
Expected: Pint, PHPStan, `types:check`, `lint:check`, `format:check`, Vitest i Pest — zielone.

- [ ] **Step 2: Push na `development`** (po akceptacji użytkownika) i zielony run `staging.yml`:

```bash
git push origin development
gh run list --workflow=staging.yml --limit 1 --json conclusion,status,createdAt
```

- [ ] **Step 3: Dowód na stagingu** (staging bez `UMAMI_*`; wymaga ciasteczka obejścia maintenance — procedura w `.claude/skills/next-task/SKILL.md`, krok 14):

```bash
curl -s -b "$COOKIE" https://morning-hub.rawendil-md2.usermd.net/ | grep -cE 'googletagmanager|gtag\(|/api/mh.js'
```

Expected: `0` — ani GA, ani trackera (analityka wyłączona).

- [ ] **Step 4: Produkcja** (wyłącznie na wyraźną prośbę — merge `development` → `master`). Użytkownik ustawia `UMAMI_URL` i `UMAMI_WEBSITE_ID` w `.env` produkcji, potem `php artisan config:cache`. Dowód:

```bash
curl -s https://morninghub.eu/ | grep -o 'src="[^"]*mh.js"[^>]*'
curl -s https://morninghub.eu/ | grep -cE 'googletagmanager|<host instancji Umami>'   # oczekiwane: 0
curl -s -o /dev/null -w '%{http_code}\n' https://morninghub.eu/api/mh.js              # 200
curl -s -o /dev/null -w '%{http_code}\n' -X POST -H 'Content-Type: application/json' -d '{}' https://morninghub.eu/api/send  # 204
```

oraz odsłona z krajem ≠ „kraj serwera" widoczna w panelu Umami (potwierdza, że instancja czyta `X-Client-IP` i że tracker akceptuje pustą odpowiedź 204).
