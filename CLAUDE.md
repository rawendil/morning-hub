<laravel-boost-guidelines>
=== .ai/analytics rules ===

# Analytics

## Umami bez cookies — nie przywracaj GA ani baneru zgody

Statystyki prowadzi self-hosted Umami (`UMAMI_URL` + `UMAMI_WEBSITE_ID` → `config('services.umami')`), obsługiwane przez `App\Services\UmamiProxyService`. Google Analytics, Consent Mode, `window.gtag` i baner cookies zostały usunięte świadomie. Umami nie zapisuje cookies ani identyfikatorów, więc podstawą jest art. 6 ust. 1 lit. f RODO, nie zgoda. Zmiana analityki wymaga poprawienia polityki prywatności (§4, §5, §11).

- **Adres instancji nie trafia do HTML.** Przeglądarka rozmawia tylko z `/api/mh.js` i `/api/send`.
- **Trasy proxy zostają w `routes/api.php`.** `api.php` rejestruje się przed `web.php`, którego catch-all SPA `/{any}` przechwyciłby każdą późniejszą trasę. Grupa `web` dokleiłaby też cookies sesji i CSRF.
- **Nie dopisuj ręcznego `page_view` w Vue Router.** Tracker sam podpina się pod `history.pushState`; ręczne wywołanie zdubluje odsłony.
- **`data-exclude-search="true"` jest obowiązkowy** — `/reset-password?token=…&email=…` wysłałby token i e-mail do statystyk.
- Lokalnie i na stagingu `UMAMI_*` zostają puste, żeby ruch dev nie trafiał do statystyk produkcji.

=== .ai/architecture rules ===

# Project Architecture

## Thin Controller, Fat Service

- Controllers should be as lean as possible — their only responsibilities are:
  - Validation (via Form Request)
  - Calling the appropriate service
  - Returning a response (Inertia::render, redirect, JSON)
- All business logic MUST live in dedicated services under `app/Services/`.
- Controllers MUST NOT contain private methods with business logic.
- Services should be injected via constructor injection, not instantiated inline with `new`.
- Every new service should:
  - Have explicit return types and type hints
  - Include PHPDoc blocks with array shapes where appropriate
  - Be created via `php artisan make:class` in the `app/Services/` directory
- When building new functionality that requires business logic, ALWAYS create a service.
- When modifying an existing controller that contains business logic — propose a refactor to a service.

=== .ai/data-storage rules ===

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

=== .ai/public-repository rules ===

# Public Repository

This repository (`rawendil/morning-hub`) is **public** on GitHub. Every commit, commit message, test and config example is world-readable, and a pushed commit cannot be taken back without rewriting history.

- Before every commit, read the whole staged diff (`git diff --cached`) and the commit message, looking for anything that must not go public:
  - secrets: tokens, keys, passwords, DSNs, bypass or cookie secrets;
  - real `.env` values, including non-secret production config such as analytics website IDs or third-party instance hosts;
  - personal data;
  - infrastructure details: SSH hosts and users, server paths, key file names;
  - details of other private repositories or projects: names, code, architecture.
- In tests and examples, use placeholders: `example.test`, `203.0.113.x`, dummy UUIDs.
- `/docs` is git-ignored on purpose. Specs and plans (`docs/superpowers/`) are local working notes and must never be committed. Do not re-add them or copy their content into tracked files.
- If a leak has already been pushed, say so immediately. Rewriting history (force-push) needs the user's explicit consent.

=== .ai/static-analysis rules ===

# Static Analysis (PHPStan / Larastan)

- After modifying PHP files, run `vendor/bin/phpstan analyse --error-format=table` on the changed files to catch type errors.
- Fix all PHPStan errors before considering a task complete.
- PHPStan configuration is in `phpstan.neon` (level 5, path `app/`).
- Do not lower the analysis level or add `@phpstan-ignore` without user approval.

=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4.26
- laravel/fortify (FORTIFY) - v1
- laravel/framework (LARAVEL) - v12
- laravel/prompts (PROMPTS) - v0
- laravel/sanctum (SANCTUM) - v4
- laravel/socialite (SOCIALITE) - v5
- larastan/larastan (LARASTAN) - v3
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- pestphp/pest (PEST) - v4
- phpunit/phpunit (PHPUNIT) - v12
- tailwindcss (TAILWINDCSS) - v4
- vue (VUE) - v3
- eslint (ESLINT) - v9
- prettier (PRETTIER) - v3

## Skills Activation

This project has domain-specific skills available. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

- `pest-testing` — Tests applications using the Pest 4 PHP framework. Activates when writing tests, creating unit or feature tests, adding assertions, testing Livewire components, browser testing, debugging test failures, working with datasets or mocking; or when the user mentions test, spec, TDD, expects, assertion, coverage, or needs to verify functionality works.
- `tailwindcss-development` — Styles applications using Tailwind CSS v4 utilities. Activates when adding styles, restyling components, working with gradients, spacing, layout, flex, grid, responsive design, dark mode, colors, typography, or borders; or when the user mentions CSS, styling, classes, Tailwind, restyle, hero section, cards, buttons, or any visual/UI changes.
- `developing-with-fortify` — Laravel Fortify headless authentication backend development. Activate when implementing authentication features including login, registration, password reset, email verification, two-factor authentication (2FA/TOTP), profile updates, headless auth, authentication scaffolding, or auth guards in Laravel applications.
- `socialite-development` — Manages OAuth social authentication with Laravel Socialite. Activate when adding social login providers; configuring OAuth redirect/callback flows; retrieving authenticated user details; customizing scopes or parameters; setting up community providers; testing with Socialite fakes; or when the user mentions social login, OAuth, Socialite, or third-party authentication.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan

- Use the `list-artisan-commands` tool when you need to call an Artisan command to double-check the available parameters.

## URLs

- Whenever you share a project URL with the user, you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain/IP, and port.

## Tinker / Debugging

- You should use the `tinker` tool when you need to execute PHP to debug code or query Eloquent models directly.
- Use the `database-query` tool when you only need to read from the database.
- Use the `database-schema` tool to inspect table structure before writing migrations or models.

## Reading Browser Logs With the `browser-logs` Tool

- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)

- Boost comes with a powerful `search-docs` tool you should use before trying other approaches when working with Laravel or Laravel ecosystem packages. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic-based queries at once. For example: `['rate limiting', 'routing rate limiting', 'routing']`. The most relevant results will be returned first.
- Do not add package names to queries; package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'.
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit".
3. Quoted Phrases (Exact Position) - query="infinite scroll" - words must be adjacent and in that order.
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit".
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms.

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.

## Constructors

- Use PHP 8 constructor property promotion in `__construct()`.
    - `public function __construct(public GitHub $github) { }`
- Do not allow empty `__construct()` methods with zero parameters unless the constructor is private.

## Type Declarations

- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<!-- Explicit Return Types and Method Params -->
```php
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
```

## Enums

- Typically, keys in an Enum should be TitleCase. For example: `FavoritePerson`, `BestLake`, `Monthly`.

## Comments

- Prefer PHPDoc blocks over inline comments. Never use comments within the code itself unless the logic is exceptionally complex.

## PHPDoc Blocks

- Add useful array shape type definitions when appropriate.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using the `list-artisan-commands` tool.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

## Database

- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries.
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `list-artisan-commands` to check the available options to `php artisan make:model`.

### APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## Controllers & Validation

- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

## Authentication & Authorization

- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Queues

- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

## Configuration

- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== laravel/v12 rules ===

# Laravel 12

- CRITICAL: ALWAYS use `search-docs` tool for version-specific Laravel documentation and updated code examples.
- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

## Laravel 12 Structure

- In Laravel 12, middleware are no longer registered in `app/Http/Kernel.php`.
- Middleware are configured declaratively in `bootstrap/app.php` using `Application::configure()->withMiddleware()`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- The `app\Console\Kernel.php` file no longer exists; use `bootstrap/app.php` or `routes/console.php` for console configuration.
- Console commands in `app/Console/Commands/` are automatically available and do not require manual registration.

## Database

- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 12 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models

- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.
- CRITICAL: ALWAYS use `search-docs` tool for version-specific Pest documentation and updated code examples.
- IMPORTANT: Activate `pest-testing` every time you're working with a Pest or testing-related task.

=== tailwindcss/core rules ===

# Tailwind CSS

- Always use existing Tailwind conventions; check project patterns before adding new ones.
- IMPORTANT: Always use `search-docs` tool for version-specific Tailwind CSS documentation and updated code examples. Never rely on training data.
- IMPORTANT: Activate `tailwindcss-development` every time you're working with a Tailwind CSS or styling-related task.

=== laravel/fortify rules ===

# Laravel Fortify

- Fortify is a headless authentication backend that provides authentication routes and controllers for Laravel applications.
- IMPORTANT: Always use the `search-docs` tool for detailed Laravel Fortify patterns and documentation.
- IMPORTANT: Activate `developing-with-fortify` skill when working with Fortify authentication features.

</laravel-boost-guidelines>
