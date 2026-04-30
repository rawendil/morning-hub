# Morning Hub

A personal morning routine dashboard that helps you organize tasks, track habits, and stay focused before starting your day. Integrates with ClickUp and RSS feeds to give you a single view of everything that matters.

**Live demo:** [morning-hub.rawendil-md2.usermd.net](https://morning-hub.rawendil-md2.usermd.net)

> **Note:** Google login and Google Calendar integration are restricted to pre-approved test users only (Google OAuth app is in testing mode). If you'd like to try the full experience, please open an issue and I'll add your Google account to the allowlist.

## Features

- **Morning Routine Blocks** - configurable routine steps with time estimates and progress tracking
- **ClickUp Integration** - pulls tasks from multiple ClickUp workspaces, grouped by priority
- **RSS Feed Reader** - stay up to date with industry news from your favorite sources
- **Today's Tasks** - dedicated view for tasks scheduled for today across all connected sources
- **Custom Blocks** - add your own routine steps with links, timers, and descriptions
- **Dark Mode** - light, dark, and system theme support
- **Onboarding Guide** - step-by-step setup wizard for new users
- **Two-Factor Auth** - secure your account with TOTP-based 2FA

## Tech Stack

**Backend:**

- PHP 8.3 / Laravel 12
- REST JSON API with Laravel Sanctum (Bearer token auth)
- Laravel Fortify (authentication logic)
- SQLite
- Pest 4 (testing)

**Frontend:**

- Vue 3 + TypeScript (SPA)
- Vue Router 4 (client-side routing)
- Pinia (state management)
- Axios (HTTP client with Bearer token interceptor)
- Tailwind CSS v4
- shadcn-vue (Reka UI)
- Lucide icons

## Requirements

- PHP >= 8.3
- Node.js >= 18
- Composer
- SQLite

## Installation

```bash
# Clone the repository
git clone https://github.com/rawendil/morning-hub.git
cd morning-hub

# Install dependencies
composer install
npm install

# Environment setup
cp .env.example .env
php artisan key:generate

# Database
touch database/database.sqlite
php artisan migrate --seed

# Build frontend assets
npm run build

# Start the application
php artisan serve
```

For development with hot reload:

```bash
composer run dev
```

## Configuration

### ClickUp Integration

1. Go to **Settings > ClickUp Connections**
2. Add your ClickUp API token
3. Configure which lists to pull tasks from

### Routine Blocks

1. Go to **Configuration > Routine Blocks**
2. Add blocks (ClickUp tasks, RSS feeds, custom links)
3. Set time estimates for each block
4. Reorder blocks by priority

## Security

### Authentication

- Password-based login and Google OAuth (via Laravel Socialite) are supported side-by-side — you can link a Google account to an existing password account and unlink it at any time.
- Authentication uses Sanctum Personal Access Tokens stored in `localStorage`. Tokens are sent as `Authorization: Bearer` headers on every API request.
- A 401 response from any API endpoint automatically clears the token and redirects to the login page.
- Login attempts are rate-limited to 10 requests per minute.

### Two-Factor Authentication (2FA)

- TOTP-based 2FA is available and can be enabled from account settings.
- Enabling 2FA requires password confirmation before setup — a compromised session alone is not enough.
- Recovery codes are generated on setup and can be regenerated at any time.

### API Token Storage

- Third-party API tokens (e.g. ClickUp) are encrypted at rest using Laravel's `encrypted` Eloquent cast, backed by `APP_KEY`.
- Tokens are hidden from model serialization and never included in API responses or logs.
- Tokens are validated against expected format patterns (e.g. ClickUp tokens must start with `pk_`) before being saved.

### Sensitive Data Handling

- Passwords and API tokens are excluded from exception reports and are never logged.
- Google OAuth credentials are stored in environment variables and accessed only through Laravel's config layer — never via `env()` directly in application code.

## License

MIT
