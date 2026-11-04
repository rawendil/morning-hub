---
paths:
  - 'resources/js/**'
---

# Js

## HTTP calls go through @/lib/axios
Make API requests with the default export of `@/lib/axios`, using paths relative to `/api`. Never import `axios` directly or call `fetch`.

## Polish source strings as translation keys
Write user-facing text in Polish wrapped in `t('…')` from `useTranslations`, with `:name` placeholders, and add the English translation to `lang/en.json` keyed by the Polish sentence. Do not add vue-i18n or dotted keys.
