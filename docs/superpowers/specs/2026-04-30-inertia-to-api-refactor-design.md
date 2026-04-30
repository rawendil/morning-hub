# Design: Migracja Inertia → REST API

**Data:** 2026-04-30
**Cel:** Wycięcie Inertia.js ze stacku i zastąpienie komunikacji frontendu z backendem przez REST API z tokenami Sanctum, jako przygotowanie do wdrożenia wersji mobilnej w Capacitor.js.

---

## 1. Kontekst i motywacja

Aplikacja Morning Hub używa Inertia.js jako warstwy łączącej Laravel (backend) z Vue 3 (frontend). Planowana migracja na Capacitor.js wymaga osobnego frontendu mobilnego. Żeby uniknąć utrzymywania dwóch backendów, podjęto decyzję o zastąpieniu Inertii czystym REST API — jeden backend obsługuje zarówno web SPA, jak i przyszłą aplikację mobilną.

**Zakres tego zadania:** wyłącznie refaktor (wycięcie Inertii, budowa API, migracja frontendu web). Wersja mobilna to osobne zadanie.

---

## 2. Architektura docelowa

**Przed:**
```
Browser → Laravel (Inertia SSR + web routes + sesja) → Vue (hydrated pages)
```

**Po:**
```
Browser → Vue SPA (Vue Router, standalone) ←→ Laravel API (JSON, Sanctum tokens)
```

**Zasady:**
- Frontend (Vue) budowany przez Vite, serwowany przez Laravel jako statyczne pliki (`public/build/`)
- Laravel obsługuje jeden catch-all web route: `GET /{any}` → `resources/views/app.blade.php`
- Cała komunikacja danych przez `/api/*` z nagłówkiem `Authorization: Bearer <token>`
- Serwisy (`app/Services/`), modele i migracje pozostają bez zmian

---

## 3. Autoryzacja i uwierzytelnianie

### 3.1 Mechanizm: Laravel Sanctum — Personal Access Tokens

- Po zalogowaniu frontend otrzymuje token Bearer
- Token przechowywany w `localStorage` na webie (docelowo `Capacitor SecureStorage` na mobile)
- Każde żądanie chronione: `Authorization: Bearer <token>`
- Token bez expiry; odwołanie przez logout (usunięcie z DB)
- Nazwa tokena: `web` (rozróżnienie urządzeń w przyszłości)

### 3.2 Endpointy auth

```
POST /api/auth/login              → {requires_2fa, temp_token} | {token, user}
POST /api/auth/two-factor         → {token, user}
POST /api/auth/logout             → 204
POST /api/auth/register           → {token, user}
POST /api/auth/forgot-password    → 204
POST /api/auth/reset-password     → 204
POST /api/auth/google             → {token, user}

GET  /api/user                    → {user, locale, appearance}
```

### 3.3 Flow: email/hasło z 2FA

1. `POST /api/auth/login` z `{email, password}`
2. Jeśli brak 2FA → zwraca `{token, user}`
3. Jeśli 2FA włączone → `Cache::put("2fa:{$tempToken}", $userId, 300)` gdzie `$tempToken = Str::random(40)`, zwraca `{requires_2fa: true, temp_token}`
4. Frontend pyta o kod TOTP
5. `POST /api/auth/two-factor` z `{temp_token, code}`
6. Backend odczytuje `$userId` z Cache, weryfikuje kod przez Fortify's `TwoFactorAuthenticationProvider::verify()`, usuwa klucz z Cache, zwraca `{token, user}`

Stan wyzwania przechowywany w Laravel Cache (TTL 5 min) — bez własnego modelu ani migracji. Weryfikacja TOTP przez Fortify's `TwoFactorAuthenticationProvider` (już zainstalowane).

### 3.4 Flow: Google OAuth (frontend-initiated)

1. Frontend inicjuje Google Sign-In przez standardową bibliotekę JS (Google Identity Services)
2. Google zwraca `access_token` po zgodzie użytkownika
3. Frontend: `POST /api/auth/google` z `{access_token}`
4. Backend: `Socialite::driver('google')->stateless()->userFromToken($accessToken)` — zwraca profil Google
5. Backend tworzy lub loguje usera, zwraca `{token, user}`

**Uwaga:** Socialite pozostaje w projekcie i obsługuje zarówno Google auth login (stateless) jak i Google Calendar OAuth (server-side redirect — sekcja 3.5). Wypadają tylko Fortify/Socialite web routes i redirect-based auth flow. Istniejący `GoogleAuthService` zostaje przepisany pod nowy flow.

### 3.5 Google Calendar OAuth — wyjątek

Google Calendar OAuth pozostaje jako server-side redirect (Google wymaga HTTPS callback URL):
- `GET /auth/google-calendar/redirect` → redirect do Google
- `GET /auth/google-calendar/callback` → po sukcesie: `redirect('/morning-hub/google-calendar?connected=true')`
- Frontend odczytuje query param i odświeża dane przez `GET /api/google-calendar`

---

## 4. Warstwa API — kontrolery

### 4.1 Struktura katalogów

```
app/Http/Controllers/
    Api/
        Auth/
            LoginController.php
            TwoFactorController.php
            LogoutController.php
            RegisterController.php        → deleguje do app/Actions/Fortify/CreateNewUser
            PasswordController.php        → deleguje do app/Actions/Fortify/ResetUserPassword
            GoogleAuthController.php
        UserController.php
        ClickUpConnectionController.php
        ClickUpApiController.php
        GoogleCalendarConnectionController.php
        GoogleCalendarApiController.php
        RoutineBlockController.php
        TodaysTasksConfigController.php
        Settings/
            ProfileController.php         → deleguje do app/Actions/Fortify/UpdateUserProfileInformation
            PasswordController.php        → deleguje do app/Actions/Fortify/UpdateUserPassword
            TwoFactorAuthenticationController.php
            AppearanceController.php
```

**Zasada:** żaden kontroler Auth nie reimplementuje logiki uwierzytelniania. Istniejące `app/Actions/Fortify/*` pozostają bez zmian — kontrolery tylko je wywołują i zwracają `JsonResponse` zamiast redirect.

### 4.2 Mapowanie odpowiedzi

| Obecny zwrot | Nowy zwrot |
|---|---|
| `Inertia::render('Page', $data)` | `JsonResponse` z danymi strony |
| `redirect()->back()` | `JsonResponse 204` lub zaktualizowany zasób |
| `redirect()->route(...)` | `JsonResponse 201` z lokalizacją zasobu |

### 4.3 Routing

- `routes/api.php` — wszystkie endpointy z prefixem `/api`, chronione przez `auth:sanctum`
- `routes/web.php` — uproszczony: catch-all SPA + Google Calendar OAuth callback
- `routes/morning-hub.php`, `routes/settings.php` — inkorporowane do `routes/api.php`

### 4.4 Usuwane komponenty

| Komponent | Powód |
|---|---|
| `HandleInertiaRequests` middleware | Dane użytkownika przez `GET /api/user` |
| `AddLinkHeadersForPreloadedAssets` middleware | Nieistotne dla API |
| `laravel/wayfinder` | Frontend nie używa Laravela do routingu |
| `@laravel/vite-plugin-wayfinder` | j.w. |
| `@inertiajs/vue3` | Zastąpiony przez Vue Router |

---

## 5. Frontend — migracja z Inertia na Vue Router SPA

### 5.1 Zależności

**Dodawane:**
- `vue-router` — routing kliencki
- `axios` — HTTP client z interceptorami (zastępuje własny fetch wrapper i `useClickUpApi.ts`)
- Google Identity Services JS (`accounts.google.com/gsi/client`) — ładowany jako zewnętrzny skrypt, bez instalacji npm

**Usuwane:**
- `@inertiajs/vue3`
- `@laravel/vite-plugin-wayfinder`

### 5.2 Mapowanie routingu Vue Router

```
/                         → Welcome.vue
/login                    → auth/Login.vue          (guestOnly)
/register                 → auth/Register.vue        (guestOnly)
/forgot-password          → auth/ForgotPassword.vue  (guestOnly)
/reset-password           → auth/ResetPassword.vue   (guestOnly)
/two-factor               → auth/TwoFactorChallenge.vue (guestOnly)
/verify-email             → auth/VerifyEmail.vue
/dashboard                → Dashboard.vue            (requiresAuth)
/todays-tasks             → TodaysTasks.vue          (requiresAuth)
/morning-hub/routine      → morning-hub/Routine.vue  (requiresAuth)
/morning-hub/clickup      → morning-hub/ClickUp.vue  (requiresAuth)
/morning-hub/google-calendar → morning-hub/GoogleCalendar.vue (requiresAuth)
/morning-hub/todays-tasks → morning-hub/TodaysTasksConfig.vue (requiresAuth)
/morning-hub/guide        → morning-hub/Guide.vue    (requiresAuth)
/settings/profile         → settings/Profile.vue     (requiresAuth)
/settings/password        → settings/Password.vue    (requiresAuth)
/settings/appearance      → settings/Appearance.vue  (requiresAuth)
/settings/two-factor      → settings/TwoFactor.vue   (requiresAuth)
```

### 5.3 Navigation guards

- `requiresAuth` — sprawdza token w `localStorage`; jeśli brak → redirect `/login`
- `guestOnly` — jeśli token istnieje → redirect `/dashboard`

### 5.4 Axios jako HTTP client

Axios zastępuje własny fetch wrapper i istniejący `useClickUpApi.ts`. Konfiguracja przez globalną instancję:

```ts
// resources/js/lib/axios.ts
axios.defaults.baseURL = '/api'
axios.interceptors.request.use(config => {
    const token = localStorage.getItem('token')
    if (token) config.headers.Authorization = `Bearer ${token}`
    return config
})
axios.interceptors.response.use(
    response => response,
    error => {
        if (error.response?.status === 401) {
            localStorage.removeItem('token')
            router.push('/login')
        }
        return Promise.reject(error)
    }
)
```

Błędy walidacji (422) dostępne przez `error.response.data.errors` — standardowy format Laravel. Composable `useApi.ts` odpada — komponenty Vue importują skonfigurowaną instancję axios bezpośrednio.

### 5.5 Stan globalny użytkownika

`useAuthStore` (Pinia lub composable) — zastępuje `HandleInertiaRequests`:
- Pobiera `GET /api/user` przy starcie aplikacji jeśli token istnieje
- Przechowuje: `user`, `locale`, `appearance`
- Aktualizowany po logowaniu/wylogowaniu

### 5.6 Inicjalizacja aplikacji (`app.ts`)

```ts
// Zamiast createInertiaApp(...)
createApp(App)
  .use(router)
  .use(pinia)        // jeśli używamy Pinia
  .mount('#app')
```

### 5.7 `app.blade.php` — uproszczony

```html
<!DOCTYPE html>
<html>
  <head>
    @vite(['resources/js/app.ts', 'resources/css/app.css'])
  </head>
  <body>
    <div id="app"></div>
  </body>
</html>
```

---

## 6. Testowanie

### 6.1 Zmiany w istniejących testach

```php
// Przed
$response->assertInertia(fn ($page) => $page->component('morning-hub/Routine'));

// Po
$response->assertStatus(200)->assertJsonStructure(['data']);
```

Wszystkie testy używające `assertInertia()` zostają przepisane na asercje JSON. Autoryzacja w testach przez `actingAs($user, 'sanctum')`.

### 6.2 Nowe testy

| Endpoint | Przypadki |
|---|---|
| `POST /api/auth/login` | sukces bez 2FA, sukces z 2FA (zwraca temp_token), błędne hasło, konto bez hasła |
| `POST /api/auth/two-factor` | poprawny kod, błędny kod, wygasły temp_token (brak w Cache), ponowne użycie temp_token |
| `POST /api/auth/google` | poprawny access_token, nowy użytkownik (rejestracja), nieprawidłowy token |
| `GET /api/user` | zwraca dane z tokenem, 401 bez tokena |
| Wszystkie chronione endpointy | 401 bez tokena, 403 przy próbie dostępu do cudzego zasobu |

### 6.3 Strategia — backend

- Testy feature przez Pest — główny ciężar testowania
- Mock `Socialite::driver('google')->stateless()->userFromToken()` — zewnętrzny serwis
- 2FA challenge: test wygasłego TTL przez `Cache::forget()` przed żądaniem
- Nie tworzymy skryptów weryfikacyjnych — testy są źródłem prawdy

### 6.4 Testy frontendowe — nowe narzędzia

Projekt nie ma testów frontendowych. Przy refaktorze dodajemy:

**Vitest** + **@vue/test-utils** — unit i component testy (natywna integracja z Vite, zero konfiguracji)
**Playwright** — E2E (dostępny w środowisku)

### 6.5 Co testujemy na frontendzie

| Obszar | Narzędzie | Przypadki |
|---|---|---|
| Axios interceptory | Vitest | Bearer token dołączany do żądań, 401 → usunięcie tokena + redirect `/login`, 422 → dostępne `error.response.data.errors` |
| Navigation guards | Vitest | `requiresAuth`: brak tokena → redirect `/login`; token istnieje → przepuszcza; `guestOnly`: token istnieje → redirect `/dashboard` |
| `useAuthStore` | Vitest | login zapisuje token w localStorage, logout usuwa token i czyści store, inicjalizacja przy starcie aplikacji gdy token istnieje |
| Formularze auth | Vue Test Utils | Login: błędy 422 wyświetlane przy polach; TwoFactorChallenge: błędny kod pokazuje komunikat |
| E2E golden path | Playwright | Logowanie email/hasło → dashboard; próba wejścia na `/dashboard` bez tokena → redirect `/login`; 2FA flow end-to-end |

---

## 7. Podejście do implementacji (Etap B)

### Etap 1 — Backend (blokuje Etap 2)

1. Dodanie Sanctum i konfiguracja tokenów
2. Kontrolery Auth API (`LoginController`, `TwoFactorController`, `GoogleAuthController`, `LogoutController`, `RegisterController`, `PasswordController`)
4. `UserController` (`GET /api/user`)
5. Przepisanie pozostałych kontrolerów na API (RoutineBlock, ClickUp, GoogleCalendar, Settings)
6. Nowy `routes/api.php`, usunięcie Inertia z `routes/web.php`
7. Usunięcie `HandleInertiaRequests` i `AddLinkHeadersForPreloadedAssets`
8. Pint + PHPStan na wszystkich zmienionych plikach
9. Przepisanie istniejących testów + nowe testy auth (mock Socialite, Cache dla 2FA)

### Etap 2 — Frontend (po zatwierdzeniu Etapu 1)

1. Dodanie Vitest + @vue/test-utils + Playwright do projektu
2. Usunięcie `@inertiajs/vue3`, dodanie `vue-router` + `axios`
3. Nowy `app.ts` z `createApp` + Vue Router
4. Uproszczony `app.blade.php`
5. Implementacja `resources/js/lib/axios.ts` (interceptory) + `useAuthStore`
6. Konfiguracja Vue Router z navigation guards
7. Testy jednostkowe: interceptory Axios, navigation guards, `useAuthStore`
8. Migracja każdej strony Vue (usunięcie `usePage`, `useForm`, `<Link>`, `<Form>`)
9. Implementacja Google Sign-In flow w `auth/Login.vue`
10. Usunięcie Wayfinder (package + Vite plugin)
11. `npm run build` + testy E2E golden path przez Playwright
