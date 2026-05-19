# Blog Article Design: Morning Hub

**Date:** 2026-05-19
**Language:** Polish
**Platform:** Own blog
**Tone:** Mix — personal opening, technical middle, personal close
**Target audience:** Developers + productivity/self-improvement readers
**Estimated length:** ~1100–1200 words

---

## Approach

Option C: Lead with "why" (book, scattered tabs), then "what" (blocks + demo), then "how" (tech decisions + commit links), close with personal reflection.

---

## Structure

### 1. Wstęp — haczyk (~100 słów)
- Opening scene: 5 tabs in the morning, no idea where to start
- Contrast: "Teraz mam jedno miejsce. Zbudowałem je sam."
- Tease: article is both about morning philosophy and technical execution

### 2. Fenomen Poranka — skąd się to wzięło (~200 słów)
- Book reference: https://lubimyczytac.pl/ksiazka/308460/fenomen-poranka
- What resonated: the idea of a consistent morning ritual for growth
- Honest note: personal blocks differ from the book's suggestions — customization is the point
- Key message: self-improvement, not productivity optimization

### 3. Moje bloki — co robi Morning Hub (~250 słów)
- RSS — industry news, keeping up with the field
- Laracasts — daily learning dose
- JeżycJadło — 5 min on a side project so it doesn't die
- ClickUp ×3 (private / project / work) — full task overview, doubles as daily standup prep
- Google Calendar — no forgotten appointments
- Optional: dashboard screenshot

### 4. Jak to powstało — historia techniczna (~350 słów)
- Prototype: ~1 week, Laravel 12 + Inertia v2 + ClickUp integration
- Gradual additions over months: Google OAuth, Calendar, "today's tasks"
- Pivot point: interest in Android mobile app → Inertia ties frontend to Laravel, pure SPA + REST API opens door to future mobile clients
- Key commits to link:
  - `d62a596` — add vue-router, axios, pinia; remove inertia and wayfinder
  - `5953a2a` — remove Inertia/Wayfinder, fix locale switching, wire Google OAuth for SPA
  - `dd26969` — remove all remaining Inertia imports, complete Vue SPA migration
- Current stack: Vue 3 + TypeScript + Pinia + Axios + Tailwind CSS v4 / Laravel 12 + Sanctum + SQLite

### 5. Czego się nauczyłem / co bym zrobił inaczej (~150 słów)
- Honest technical reflection
- Inertia was great for fast prototyping — no regrets, but would start with SPA from day one now
- Side project pacing: done in sprints, that's fine

### 6. Zakończenie — codzienne używanie (~100 słów)
- Return to personal tone
- Don't always finish all blocks — but always finish Laracasts
- The feeling of having done something for yourself before the day takes over
- Links: GitHub repo, live demo

---

## Key Links

- Live demo: https://morning-hub.rawendil-md2.usermd.net
- GitHub: https://github.com/rawendil/morning-hub
- Book: https://lubimyczytac.pl/ksiazka/308460/fenomen-poranka
