# Data Storage Strategy

The line runs between **UI state** and **domain events**, not between "ephemeral" and "permanent".

- **UI state** is how the screen currently looks to one person in one browser: a running countdown, a collapsed section, a chosen theme. Losing it costs nothing. It stays on the client.
- **Domain events** are facts about the user: a habit checked off, a routine block finished and how long it took. They are the only record of what the app is for. They go to the database, keyed to the user's local day.

## Where to store data

| Category | Location | Examples |
|---|---|---|
| Structural configuration | Database | Routine blocks, API connections |
| Sensitive data (tokens, keys) | Database (encrypted) | `api_token` in `clickup_connections` |
| Domain events (what the user did) | Database, keyed by `user_id` + `local_date` | `daily_habit_completions`, `daily_block_completions` |
| Running UI state | `localStorage` | Remaining seconds and active block in `useRoutineTimer.ts` |
| Per-browser preferences | `localStorage` (optionally with TTL) | Read articles, onboarding flag, timer sound |
| Legal consent | `localStorage` (the operative gate is per browser) | Cookie consent |
| UI preferences needing the server | `localStorage` + cookie | Light/dark mode |
| External data (API) | Nowhere — fetch live | ClickUp tasks, RSS articles, calendar events |

## Decision rules

- **Is it a fact about what the user did?** → Database. It is worth keeping even if the feature using it does not exist yet.
- **Does it need to be the same on another device?** → Database.
- **Does the server need to act on it without a browser open** (notification, digest, streak)? → Database.
- **Does it change every second?** → `localStorage`. Persist to the server on state transitions only, never on the tick.
- **Is it how this one screen currently looks?** → `localStorage`.
- **Is it sensitive or does it require integrity?** → Database (users can edit `localStorage` in DevTools).

## The user's day

A "day" is the user's local calendar day, not UTC and not the server's timezone.

- `users.timezone` holds an IANA identifier, detected in the browser and synced by `useTimezoneSync.ts`.
- `User::localDate()` is the only way to ask what day it is for someone. It falls back to UTC for a missing or unknown timezone.
- Never derive a date with `new Date().toISOString().slice(0, 10)` — that is the UTC date, which rolls over mid-evening for users east of Greenwich.
- Store it as a plain `YYYY-MM-DD` string. It is a calendar date with no time and no offset; casting it to a datetime reintroduces the timezone that was just resolved.

## Writing to the server

Writes are server-first with optimistic UI: apply locally, send, and roll back with a toast if the request fails. See `useDailyProgress.ts`. There is no offline queue and no conflict resolution — an offline change is lost and the user is told.

Completion tables are keyed by a unique index on `(user_id, …, local_date)`, so every write is an idempotent upsert or a delete. Repeating a request is always safe.

## Referring to things that history outlives

Anything that completion history points at needs an identifier that survives editing. Habits carry a generated `id` in `routine_blocks.config.habits` (`{id, label}`) precisely so that renaming or reordering them does not rewrite the past. Never key history by array position.

## What NOT to do

- Do NOT use PHP sessions for UI or daily state — sessions expire and require a request on every change.
- Do NOT put a per-second timer tick behind an HTTP request.
- Do NOT store external (API) data locally — always fetch it live.
- Do NOT key anything durable by array index.
