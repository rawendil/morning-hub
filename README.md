# Morning Hub

A personal morning routine dashboard that helps you organize tasks, track habits, and stay focused before starting your day. Integrates with ClickUp and RSS feeds to give you a single view of everything that matters.

**Live demo:** [morning-hub.REDACTED_USER.usermd.net](https://morning-hub.REDACTED_USER.usermd.net)

> **Note:** Google login and Google Calendar integration are restricted to pre-approved test users only (Google OAuth app is in testing mode). If you'd like to try the full experience, please open an issue and I'll add your Google account to the allowlist.

![Welcome](docs/screenshots/welcome.png)

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
- Inertia.js v2 (server-side)
- Laravel Fortify (authentication)
- Laravel Wayfinder (type-safe routes)
- SQLite
- Pest 4 (testing)

**Frontend:**

- Vue 3 + TypeScript
- Inertia.js v2 (client-side)
- Tailwind CSS v4
- shadcn-vue (Reka UI)
- Lucide icons
- VueUse

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

## License

MIT
