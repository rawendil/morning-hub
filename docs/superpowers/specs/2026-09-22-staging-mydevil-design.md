# Staging na MyDevil — projekt

Data: 2026-09-22

## Problem

Jedyną drogą na serwer jest push na `master`, który wdraża produkcję
(`morninghub.eu`). Skill `/next-task` ma twardą bramkę „dowód na żywo", więc
domknięcie każdego zadania z widocznym efektem wymaga wdrożenia produkcyjnego —
także wtedy, gdy zmiana nie jest gotowa na wydanie.

## Rozwiązanie

Trzecie środowisko na tym samym koncie MyDevil, wdrażane z gałęzi
`development`, oraz przestawienie bramki dowodu w `/next-task` na staging.

| | produkcja | staging |
|---|---|---|
| Domena | `morninghub.eu` | `morning-hub.rawendil-md2.usermd.net` |
| Katalog wdrożenia | sekret `DEPLOY_PATH` | sekret `DEPLOY_PATH_STAGING` |
| Gałąź | `master` | `development` |
| Workflow | `.github/workflows/deploy.yml` | `.github/workflows/staging.yml` |
| `APP_ENV` | `production` | `staging` |
| Baza | własny `database/database.sqlite` | **osobny** plik SQLite |
| PHP | `php83` | `php83` (parytet) |

## Decyzje

**Osobny plik workflow, nie jeden z warunkami.** Produkcja i staging są
całkowicie rozdzielone — błąd w jednym pliku nie może zatrzymać drugiego
środowiska. `staging.yml` jest lustrem `deploy.yml`: ta sama brama jakości
(`composer ci`), ten sam kształt kroków, inny branch i inna ścieżka.

**Brama jakości identyczna, nie słabsza.** Staging ma być wiarygodnym dowodem,
więc `composer ci` blokuje wdrożenie tak samo jak na produkcji.

**Osobna baza SQLite.** Oba środowiska stoją na jednym koncie SSH.
`artisan migrate --force` uruchomiony pod złą ścieżką uszkodziłby produkcyjne
dane — katalogi są rozdzielone i nic ich nie łączy.

**`APP_ENV=staging`, nie `production`.** Laravel traktuje `production`
specjalnie (m.in. `DB::prohibitDestructiveCommands`), a staging ma być
środowiskiem, które wolno wywalić do zera.

**Redirect URI OAuth.** `GOOGLE_REDIRECT_URI` i `CLICKUP_REDIRECT_URI`
wyprowadzają się z `APP_URL`, więc staging generuje własne adresy zwrotne.
Bez wpisania ich w konsolach Google i ClickUp logowanie na stagingu nie
zadziała — to czynność poza repozytorium.

## Poza zakresem repozytorium

`.claude/skills/next-task` i `.claude/skills/add-task` są w `.gitignore`
(personalny workflow). Poprawka bramki dowodu jest zmianą **lokalną** — nie
pojawi się w commicie ani na innych maszynach.
