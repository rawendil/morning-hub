# Analytics

## Umami bez cookies — nie przywracaj GA ani baneru zgody

Statystyki prowadzi self-hosted Umami (`UMAMI_URL` + `UMAMI_WEBSITE_ID` → `config('services.umami')`), obsługiwane przez `App\Services\UmamiProxyService`. Google Analytics, Consent Mode, `window.gtag` i baner cookies zostały usunięte świadomie. Umami nie zapisuje cookies ani identyfikatorów, więc podstawą jest art. 6 ust. 1 lit. f RODO, nie zgoda. Zmiana analityki wymaga poprawienia polityki prywatności (§4, §5, §11).

- **Adres instancji nie trafia do HTML.** Przeglądarka rozmawia tylko z `/api/mh.js` i `/api/send`.
- **Trasy proxy zostają w `routes/api.php`.** `api.php` rejestruje się przed `web.php`, którego catch-all SPA `/{any}` przechwyciłby każdą późniejszą trasę. Grupa `web` dokleiłaby też cookies sesji i CSRF.
- **Nie dopisuj ręcznego `page_view` w Vue Router.** Tracker sam podpina się pod `history.pushState`; ręczne wywołanie zdubluje odsłony.
- **`data-exclude-search="true"` jest obowiązkowy** — `/reset-password?token=…&email=…` wysłałby token i e-mail do statystyk.
- Lokalnie i na stagingu `UMAMI_*` zostają puste, żeby ruch dev nie trafiał do statystyk produkcji.
