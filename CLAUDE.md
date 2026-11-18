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
| Per-account state that must follow the user across devices | Database, keyed by `user_id` | `read_articles` (30-day retention, pruned on write) |
| Running UI state | `localStorage` | Remaining seconds and active block in `useRoutineTimer.ts` |
| Per-browser preferences | `localStorage` (optionally with TTL) | Onboarding flag, timer sound |
| Acceptance of legal documents | Database, keyed by `user_id` | `users.terms_accepted_at` (set at registration) |
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

=== .ai/formatting rules ===

# Formatting (Pint + Prettier)

## Blade templates are formatted by Pint, not by Prettier

`pint.json` enables `Pint/laravel_blade`. Pint formats `.blade.php` files through its own Prettier worker, using the configuration bundled with Pint (`vendor/laravel/pint/resources/prettier/prettierrc.json`: parser `blade`, `printWidth` 120). It does not read the project `.prettierrc`.

- **Do not add `prettier-plugin-blade` to `.prettierrc`.** `npm run format` would then format Blade too, with different options than Pint, and the two formatters would keep rewriting each other's output.
- **Pint now needs Node and installed npm packages.** Run `npm ci` before `composer lint` or `composer ci`. Without `prettier`, `prettier-plugin-blade` and `prettier-plugin-tailwindcss` in `node_modules`, Pint stops with an error instead of formatting.
- **Bumping Pint can raise the npm minimums.** Pint checks the versions it requires (`prettierDependencies()` in its Blade fixer) and refuses to run when the installed packages are older. Update `package.json` in the same change.
- Pint never formats `resources/views/emails/`, `resources/views/mail/`, `Envoy.blade.php` or `resources/boost/guidelines/`, even with the rule enabled.

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

## Foundational Context

This application is a Laravel application running on PHP 8.4. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

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

- If a frontend change doesn't show in the UI or you get a "Unable to locate file in Vite manifest" error, run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists, including path-scoped framework guidelines under `.ai/rules/boost`. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== tests rules ===

# Test Enforcement

- Add or update tests for behavior and logic changes when a test provides meaningful regression coverage.
- Pure copy, styling, and layout-only changes do not require new or updated tests.
- When test coverage applies, run the affected tests and ensure they pass.
- Test the changed behavior and its important failure modes, but do not add tests beyond them.
- Read the `testing-best-practices` skill before writing tests.

=== laravel-fortify/core rules ===

# Laravel Fortify

- Fortify is a headless authentication backend that provides authentication routes and controllers for Laravel applications.
- IMPORTANT: Always use the `search-docs` tool for detailed Laravel Fortify patterns and documentation.
- IMPORTANT: Activate `developing-with-fortify` skill when working with Fortify authentication features.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

=== laravel/v12 rules ===

# Laravel 12

- CRITICAL: ALWAYS use `search-docs` tool for version-specific Laravel documentation and updated code examples.
- This project uses the streamlined Laravel 11+ structure: register middleware, exceptions, and routing in `bootstrap/app.php` and service providers in `bootstrap/providers.php`. There is no `app/Http/Kernel.php` or `app/Console/Kernel.php`, and commands in `app/Console/Commands/` auto-register.

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

# Pest

- This project uses Pest. Create tests with `php artisan make:test --pest {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.
- Do not delete tests or test files without approval. They are part of the application.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/pest` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.

</laravel-boost-guidelines>
