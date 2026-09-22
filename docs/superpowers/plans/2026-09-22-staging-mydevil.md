# Staging na MyDevil — plan wdrożenia

Data: 2026-09-22 · Specyfikacja: `../specs/2026-09-22-staging-mydevil-design.md`

## 1. Serwer (MyDevil — konto SSH w sekretach repo: `SSH_USERNAME`@`SSH_HOST`)

⚠️ Na tym koncie stoi **produkcja**. Każda komenda celuje w katalog stagingu
(sekret `DEPLOY_PATH_STAGING`) — nigdy w katalog produkcji ani w skrót `~/domains/*`.

- [ ] `devil www add morning-hub.rawendil-md2.usermd.net php`
- [ ] `devil ssl www add <IP serwera WWW> le le morning-hub.rawendil-md2.usermd.net`
- [ ] `devil www options morning-hub.rawendil-md2.usermd.net sslonly on`
- [ ] Klon repo do `<katalog domeny>/morning-hub`, gałąź `development`
- [ ] `public_html` → symlink na `morning-hub/public`
- [ ] `php_openbasedir` na katalog aplikacji
- [ ] `.htaccess` katalogu domeny: `X-Robots-Tag "noindex, nofollow, noarchive"`
- [ ] `.env`: `APP_ENV=staging`, własny `APP_KEY`, `APP_URL` stagingu, własny SQLite
- [ ] `composer install`, `npm install`, `npm run build`, `migrate --force`

## 2. Gałąź i workflow

- [ ] `development` z `master`, wypchnięta na `origin`
- [ ] `.github/workflows/staging.yml` — lustro `deploy.yml`, trigger `push: [development]`
- [ ] Sekret `DEPLOY_PATH_STAGING`
- [ ] Weryfikacja: push na `development` rusza staging i **nie** rusza produkcji

## 3. Repozytorium i skille

- [ ] `README.md:5` — „Live demo" → `https://morninghub.eu`
- [ ] `.env.example` — komentarz o wariancie stagingowym
- [ ] `.claude/skills/next-task/SKILL.md` — bramka dowodu, definicja ukończenia,
      krok 13 (push), sekcja „Git", tabela „Częste błędy" (zmiana **lokalna**)
- [ ] `.claude/skills/add-task/SKILL.md` — domyślne DoD (zmiana **lokalna**)

## 4. Weryfikacja

- [ ] `composer ci` zielone
- [ ] Commit na `development` widoczny na stagingu, produkcja nietknięta
- [ ] Osobna baza — zapis na stagingu niewidoczny na produkcji
