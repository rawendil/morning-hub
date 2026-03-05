# Static Analysis (PHPStan / Larastan)

- Po modyfikacji plików PHP, uruchom `vendor/bin/phpstan analyse --error-format=table` na zmienionych plikach, aby wykryć błędy typów.
- Napraw wszystkie błędy PHPStan przed uznaniem zadania za zakończone.
- Konfiguracja PHPStan znajduje się w `phpstan.neon` (level 5, ścieżka `app/`).
- Nie obniżaj poziomu analizy ani nie dodawaj `@phpstan-ignore` bez zgody użytkownika.
