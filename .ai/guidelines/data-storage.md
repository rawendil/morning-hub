# Data Storage Strategy

Projekt stosuje zasadę minimalnego przechowywania danych w bazie. Dane efemeryczne żyją po stronie klienta.

## Gdzie przechowywać dane

| Kategoria | Lokalizacja | Przykłady |
|---|---|---|
| Konfiguracja strukturalna | Baza danych | Bloki rutyny, połączenia API |
| Dane wrażliwe (tokeny, klucze) | Baza danych (encrypted) | `api_token` w `clickup_connections` |
| Efemeryczny stan dzienny | `localStorage` z daily reset | Timer rutyny, ukończenie nawyków |
| Preferencje per-browser | `localStorage` (opcjonalnie z TTL) | Przeczytane artykuły, onboarding flag |
| Preferencje UI z SSR | `localStorage` + cookie | Tryb jasny/ciemny (cookie dla SSR) |
| Dane zewnętrzne (API) | Nigdzie — `Inertia::defer()` | Taski ClickUp, artykuły RSS |
| Prosty UI state | Cookie | Stan sidebara |

## Zasady decyzyjne

- **Serwer potrzebuje danych przy renderowaniu?** → Baza danych lub `Inertia::defer()`
- **Dane zmieniają się często (co sekundę)?** → `localStorage` (zero kosztu serwera)
- **Dane żyją max 1 dzień?** → `localStorage` z daily reset pattern (porównanie `date` z `todayString()`)
- **Utrata danych jest akceptowalna?** → `localStorage`
- **Dane muszą przetrwać między urządzeniami?** → Baza danych
- **Dane są wrażliwe lub wymagają integralności?** → Baza danych (użytkownik może edytować localStorage w DevTools)

## Pattern: localStorage z daily reset

Wzorzec stosowany w `useRoutineTimer.ts` i `useHabitsStorage.ts`:

```ts
type StoredState = {
    date: string; // 'YYYY-MM-DD'
    // ... dane specyficzne dla composable
};

function loadState(): StoredState | null {
    const raw = localStorage.getItem(STORAGE_KEY);
    const parsed = JSON.parse(raw);
    if (parsed.date !== todayString()) {
        localStorage.removeItem(STORAGE_KEY);
        return null; // reset na nowy dzień
    }
    return parsed;
}
```

## Czego NIE robić

- NIE używaj sesji PHP do przechowywania stanu UI/dziennego — sesja wygasa i wymaga requestu przy każdej zmianie.
- NIE twórz tabel w bazie dla danych, których utrata nie jest problemem.
- NIE przechowuj danych zewnętrznych (API) lokalnie — zawsze pobieraj na żywo przez `Inertia::defer()`.
