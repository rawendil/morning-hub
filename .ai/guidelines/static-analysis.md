# Static Analysis (PHPStan / Larastan)

- After modifying PHP files, run `vendor/bin/phpstan analyse --error-format=table` on the changed files to catch type errors.
- Fix all PHPStan errors before considering a task complete.
- PHPStan configuration is in `phpstan.neon` (level 5, path `app/`).
- Do not lower the analysis level or add `@phpstan-ignore` without user approval.
