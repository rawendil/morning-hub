# Project Architecture

## Thin Controller, Fat Service

- Kontrolery powinny być maksymalnie chude — ich jedyną odpowiedzialnością jest:
  - Walidacja (przez Form Request)
  - Wywołanie odpowiedniego serwisu
  - Zwrócenie odpowiedzi (Inertia::render, redirect, JSON)
- Cała logika biznesowa MUSI być w dedykowanych serwisach w `app/Services/`.
- Kontrolery NIE MOGĄ zawierać prywatnych metod z logiką biznesową.
- Serwisy powinny być wstrzykiwane przez constructor injection, nie tworzone inline przez `new`.
- Każdy nowy serwis powinien:
  - Mieć explicit return types i type hints
  - Posiadać PHPDoc bloki z array shapes tam gdzie to sensowne
  - Być tworzony przez `php artisan make:class` w katalogu `app/Services/`
- Gdy tworzysz nową funkcjonalność wymagającą logiki biznesowej, ZAWSZE utwórz serwis.
- Przy modyfikacji istniejącego kontrolera, który zawiera logikę biznesową — zaproponuj refaktor do serwisu.
