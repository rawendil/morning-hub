# GA4 → Umami (proxy przez domenę Morning Hub) — design

**Data:** 2026-09-30
**Status:** do akceptacji

## Cel

Zastąpić Google Analytics 4 własną instancją Umami, serwowaną przez domenę
Morning Hub, i usunąć baner zgody na cookies.

Kryteria sukcesu:

- na produkcji (`morninghub.eu`) źródło strony nie zawiera `googletagmanager`,
  `gtag(` ani adresu instancji Umami — tracker i beacon idą przez naszą domenę;
- odsłony SPA (w tym zmiany tras Vue Router) trafiają do Umami z prawdziwym
  krajem, urządzeniem i językiem odwiedzającego;
- awaria instancji Umami nie psuje ani nie spowalnia aplikacji;
- baner cookies i link „Ustawienia cookies" znikają; polityka prywatności
  opisuje stan faktyczny.

## Kontekst

Dziś GA4 ładuje się z [spa.blade.php](../../../resources/views/spa.blade.php)
(Consent Mode v2, `analytics_storage: denied`), a zgodą steruje
`useCookieConsent.ts` + `CookieConsentModal.vue`. Martwy
`resources/views/app.blade.php` (relikt po Inertii, żadna trasa go nie renderuje)
trzyma kopię tego samego snippetu.

Wzorzec do przeniesienia: to samo proxy (skrypt i beacon przez domenę serwisu)
działa już w innym projekcie na MyDevil, a pułapka ręcznych `page_view` jest
znana z wcześniejszej migracji GA → Umami.

## Decyzje

| Decyzja | Wybór | Uzasadnienie |
| --- | --- | --- |
| Hosting | istniejąca instancja Umami | decyzja użytkownika |
| Zgoda | baner usunięty | Umami nie zapisuje cookies ani identyfikatorów → art. 6 ust. 1 lit. f RODO, zgoda zbędna |
| Transport | proxy przez domenę Morning Hub | odporność na blokery; adres instancji nie pojawia się w HTML |
| Logika proxy | `UmamiProxyService`, cienkie kontrolery | reguła „thin controller, fat service" z `CLAUDE.md` |
| Query string | `data-exclude-search="true"` | `/reset-password?token=…&email=…` wysłałoby token i e-mail do statystyk |
| Nagłówek `CF-Connecting-IP` | pominięty | Morning Hub nie stoi za Cloudflare; `trustProxies(at: '*')` daje poprawne `$request->ip()` |
| Staging i lokalnie | puste `UMAMI_*` = brak analityki | ruch dev nie śmieci statystyk |

## Architektura

```text
przeglądarka ── GET  /api/mh.js ──► UmamiScriptController ─┐
             └─ POST /api/send  ──► UmamiCollectController ─┤
                                                            ▼
                                                   UmamiProxyService ──HTTP──► instancja Umami
                                                   (cache skryptu 24 h)        /script.js, /api/send
```

### Trasy — obie w `routes/api.php`

Naturalne miejsce na trasę skryptu to `withRouting(then: …)`, ale **w Morning Hub
to nie zadziała:** Laravel rejestruje kolejno `api` → `web` → `then`, a catch-all
SPA `Route::get('/{any}')` w `web.php` (zwykła trasa, nie `Route::fallback`)
wygrywa z każdą trasą zarejestrowaną po nim — `/site.js` zwróciłby HTML SPA.

Obie trasy idą więc do `api.php`, rejestrowanego **przed** `web`:

- `GET /api/mh.js` → `UmamiScriptController`
  (nazwa neutralna — nie `script.js` / `umami.js`, które są typowymi wzorcami
  blokerów);
- `POST /api/send` → `UmamiCollectController`, `throttle:120,1`. Ścieżka jest
  wymuszona przez tracker (`${data-host-url}/api/send`).

Grupa `api` w tym projekcie nie dokleja sesji ani XSRF (brak `statefulApi()`,
`HandleLocale` tylko czyta cookie), więc obie odpowiedzi pozostają bez
`Set-Cookie` i bez CSRF — beacon nie dostanie 419, a skrypt może mieć
`Cache-Control: public`.

### `App\Services\UmamiProxyService`

- `isEnabled(): bool` — obie wartości configu niepuste.
- `trackerScript(): ?string` — skrypt z `{upstream}/script.js`, trzymany
  24 h w cache'u aplikacji (`Cache::get` / `Cache::put`); awaria (timeout 3/5 s, wyjątek, nie-2xx)
  → `null` i **nic nie trafia do cache'u**.
- `forwardEvent(string $payload, string $clientIp, string $userAgent, string $acceptLanguage): void`
  — `POST {upstream}/api/send` z treścią 1:1 i nagłówkami `X-Client-IP`,
  `User-Agent`, `Accept-Language`; timeout 2 s; każdy wyjątek połknięty.
- upstream normalizowany (`rtrim('/')`).

### Kontrolery (cienkie)

- `UmamiScriptController`: 404 gdy wyłączone; skrypt → 200
  `text/javascript`, `max-age=86400`; `null` → pusty body, `max-age=60`.
- `UmamiCollectController`: 404 gdy wyłączone; woła serwis z
  `$request->getContent()`, `$request->ip()`, UA, `Accept-Language`;
  zawsze `204`.

### Konfiguracja

- `config/services.php`: `google.analytics_id` → `umami.url`, `umami.website_id`
  (`UMAMI_URL`, `UMAMI_WEBSITE_ID`).
- `.env.example`: `VITE_GA_MEASUREMENT_ID` → `UMAMI_URL=` i `UMAMI_WEBSITE_ID=`
  z komentarzem, że URL nigdy nie trafia do HTML.

### Tag w `spa.blade.php`

Blok gtag zastąpiony (renderowany tylko gdy serwis `isEnabled()`):

```blade
<script defer src="{{ url('/api/mh.js') }}"
    data-website-id="{{ config('services.umami.website_id') }}"
    data-host-url="{{ url('/') }}"
    data-exclude-search="true"
    data-do-not-track="true"></script>
```

`data-do-not-track` respektuje ustawienie DNT przeglądarki (polityka ma już
sekcję 11 „Do Not Track"). Brak ręcznego `page_view` w Vue Router — tracker sam
podpina się pod `history.pushState`.

Strony `privacy-policy` i `terms-of-service` (osobne widoki Blade) nie są
śledzone.

### Usunięcia (frontend)

- `components/CookieConsentModal.vue`, `composables/useCookieConsent.ts`;
- `<CookieConsentModal />` w `App.vue`, pozycja „Ustawienia cookies" w
  `AppSidebar.vue` (+ import ikony `Cookie`, jeśli nieużywana gdzie indziej);
- `gtag` i `dataLayer` z `types/global.d.ts`;
- klucze `lang/en.json` używane wyłącznie przez modal (`Pliki cookies`,
  `Ustawienia cookies`, treść modalu, `Odrzuć`/`Akceptuj` — tylko jeśli nieużywane
  gdzie indziej);
- `resources/views/app.blade.php` (martwy).

Pozostały w `localStorage` klucz `cookie_consent` jest nieszkodliwy — nie
czyścimy go.

### Polityka prywatności (`privacy-policy.blade.php`)

- §4: „Analityka webowa: Google Analytics, Google Tag Manager" → Umami (własna
  instancja).
- §5: akapit „Google Analytics" zastąpiony opisem Umami: bez cookies i
  identyfikatorów, skrypt i zdarzenia przez domenę serwisu, IP użyte tylko do
  kraju i nieprzechowywane, adresy bez parametrów zapytania, wyłączenie przez
  DNT lub `localStorage` `umami.disabled = 1`.
- §7 bez zmian: wymienia Google (OAuth), ClickUp i Sentry, nie Analytics;
  instancja Umami stoi w UE.

### Reguła dla agentów

Nowy `.ai/guidelines/analytics.md`: Umami bez cookies — nie
przywracać GA ani banera; nie dopisywać ręcznego `page_view`; trasy proxy muszą
zostać w `api.php` (catch-all SPA); `data-exclude-search` jest obowiązkowy.

## Obsługa błędów

| Sytuacja | Zachowanie |
| --- | --- |
| brak configu | tag nie renderuje się; obie trasy → 404 |
| instancja nie odpowiada przy pobraniu skryptu | pusty skrypt, `max-age=60`, brak wpisu w cache |
| instancja nie odpowiada przy beaconie | 204, zdarzenie tracone, brak wyjątku |
| zalew beaconów | `throttle:120,1` → 429 |

## Testy

Pest, z `Http::fake()`:

- **`UmamiProxyServiceTest`**: cache skryptu (drugie wywołanie bez HTTP); awaria
  → `null` i pusty cache; trailing slash upstreamu; forward niesie body 1:1 i trzy
  nagłówki; wyjątek HTTP połknięty.
- **`UmamiProxyRoutesTest`**: `GET /api/mh.js` → JS i nagłówki cache; pusty
  skrypt → `max-age=60`; `POST /api/send` → 204 także przy 500 upstreamu;
  wyłączone → 404 na obu; brak `Set-Cookie` na obu odpowiedziach; `/api/mh.js`
  **nie** zwraca HTML SPA (strażnik kolejności tras).
- **`UmamiScriptTagTest`** (na `/`): tag z `src`, `data-website-id`,
  `data-host-url`, `data-exclude-search`; adres upstreamu **nieobecny** w HTML;
  brak tagu bez configu; brak `googletagmanager`, `gtag(`, `dataLayer`.
- **`PrivacyPolicyTest`**: wzmianka o Umami, brak „Google Analytics".
- Vitest: brak testów modalu do usunięcia; `composer ci` potwierdza, że typy i
  importy się spinają.

## Wdrożenie

1. Merge na `development` → staging (bez `UMAMI_*`: sprawdzamy brak GA i brak
   baneru).
2. Produkcja: `UMAMI_URL`, `UMAMI_WEBSITE_ID` w `.env`, `config:cache`.
3. Dowód: źródło `morninghub.eu` zawiera `/api/mh.js`, nie zawiera
   `googletagmanager` ani hosta Umami; `curl -X POST /api/send` → 204; odsłona
   widoczna w panelu Umami.
4. Po stronie użytkownika: website w Umami dla `morninghub.eu`; wyłączenie
   property GA4, gdy przestanie być potrzebne.

## Poza zakresem

Zdarzenia własne (np. „ukończono rutynę"), migracja historii z GA4, GSC,
proxy przez Cloudflare.
