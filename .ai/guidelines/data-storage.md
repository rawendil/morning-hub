# Data Storage Strategy

The project follows a minimal database storage principle. Ephemeral data lives on the client side.

## Where to store data

| Category | Location | Examples |
|---|---|---|
| Structural configuration | Database | Routine blocks, API connections |
| Sensitive data (tokens, keys) | Database (encrypted) | `api_token` in `clickup_connections` |
| Ephemeral daily state | `localStorage` with daily reset | Routine timer, habit completion |
| Per-browser preferences | `localStorage` (optionally with TTL) | Read articles, onboarding flag |
| UI preferences with SSR | `localStorage` + cookie | Light/dark mode (cookie for SSR) |
| External data (API) | Nowhere — `Inertia::defer()` | ClickUp tasks, RSS articles |
| Simple UI state | Cookie | Sidebar state |

## Decision rules

- **Does the server need the data at render time?** → Database or `Inertia::defer()`
- **Does the data change frequently (every second)?** → `localStorage` (zero server cost)
- **Does the data live at most 1 day?** → `localStorage` with daily reset pattern (compare `date` with `todayString()`)
- **Is data loss acceptable?** → `localStorage`
- **Must the data persist across devices?** → Database
- **Is the data sensitive or requires integrity?** → Database (users can edit localStorage in DevTools)

## Pattern: localStorage with daily reset

Pattern used in `useRoutineTimer.ts` and `useHabitsStorage.ts`:

```ts
type StoredState = {
    date: string; // 'YYYY-MM-DD'
    // ... composable-specific data
};

function loadState(): StoredState | null {
    const raw = localStorage.getItem(STORAGE_KEY);
    const parsed = JSON.parse(raw);
    if (parsed.date !== todayString()) {
        localStorage.removeItem(STORAGE_KEY);
        return null; // reset on new day
    }
    return parsed;
}
```

## What NOT to do

- Do NOT use PHP sessions to store UI/daily state — sessions expire and require a request on every change.
- Do NOT create database tables for data whose loss is not a problem.
- Do NOT store external (API) data locally — always fetch live via `Inertia::defer()`.
