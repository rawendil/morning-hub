# ClickUp OAuth Integration Design

**Date:** 2026-05-17
**Status:** Approved

## Summary

Replace the manual `pk_` API token form for ClickUp connections with an OAuth 2.0 Authorization Code flow. Users type a connection name, click "Połącz z ClickUp", and are redirected through ClickUp's consent screen. The resulting access token is saved automatically. Multiple connections per user remain supported (for users with multiple ClickUp accounts).

## Architecture

### New files

- `app/Services/ClickUpOAuthService.php` — builds the ClickUp authorization URL, exchanges `code` for access token via Http client
- `app/Http/Controllers/MorningHub/ClickUpOAuthController.php` — two actions: `redirect` and `callback`

### Modified files

| File | Change |
|---|---|
| `config/services.php` | Add `clickup` key (`client_id`, `client_secret`, `redirect`) |
| `routes/web.php` | Add `GET /clickup/oauth/redirect` and `GET /clickup/oauth/callback` (session auth) |
| `routes/api.php` | Remove `POST /morning-hub/clickup/connections` route |
| `app/Http/Controllers/Api/ClickUpConnectionController.php` | Remove `store` action |
| `app/Http/Requests/MorningHub/StoreClickUpConnectionRequest.php` | Delete entirely |
| `app/Http/Requests/MorningHub/UpdateClickUpConnectionRequest.php` | Remove `api_token` field |
| `app/Models/ClickUpConnection.php` | Remove `tokenFormatPattern()` — token origin is now OAuth, not user input |
| `resources/js/components/morning-hub/ClickUpConnectionForm.vue` | Replace token input with name-only form; on submit do `window.location.href` to redirect route |
| `resources/js/pages/morning-hub/ClickUp.vue` | On mount, read `?connected=1` or `?error=...`, show toast, clear query params via `router.replace` |

### Unchanged

- `app/Services/ClickUpService.php` — still uses `api_token`, no changes
- `app/Http/Controllers/Api/ClickUpApiController.php` — all proxy endpoints unchanged
- `app/Models/Concerns/HasEncryptedCredentials.php` — token still encrypted at rest
- Delete and update connection flows

## OAuth Flow

### Step 1 — User initiates

Dialog shows one field ("Nazwa") and a "Połącz z ClickUp" button. On submit:

```ts
window.location.href = `/clickup/oauth/redirect?name=${encodeURIComponent(name)}`
```

### Step 2 — Redirect (`GET /clickup/oauth/redirect`)

Web route, protected by `auth` middleware (session).

`ClickUpOAuthService::buildRedirectResponse()`:
1. Reads `name` from query param, stores in session as `clickup_oauth_name`
2. Generates `state = Str::random(40)`, stores in session as `clickup_oauth_state`
3. Returns redirect to:
   ```
   https://app.clickup.com/api?client_id={id}&redirect_uri={uri}&state={state}
   ```

### Step 3 — Callback (`GET /clickup/oauth/callback`)

Web route, protected by `auth` middleware (session).

`ClickUpOAuthController::callback()`:
1. Check `code` present → else redirect with `?error=no_code`
2. Validate `state` matches session `clickup_oauth_state` → else redirect with `?error=invalid_state`
3. Call `ClickUpOAuthService::exchangeToken($code)`:
   - `POST https://api.clickup.com/api/v2/oauth/token` with `client_id`, `client_secret`, `code`
   - Returns access token string or throws on failure
4. Create `ClickUpConnection` for authenticated user with token and name from session
5. Clear session keys (`clickup_oauth_state`, `clickup_oauth_name`)
6. Redirect to `/morning-hub/clickup?connected=1`

On any failure → redirect to `/morning-hub/clickup?error={reason}`

### Error codes

| Code | Cause |
|---|---|
| `no_code` | ClickUp did not return a code (user cancelled or error) |
| `invalid_state` | State mismatch — CSRF protection triggered |
| `auth_failed` | Token exchange HTTP request failed or returned error |

## Frontend feedback

In `ClickUp.vue` `onMounted`:

```ts
const connected = route.query.connected
const error = route.query.error

if (connected) toast.success('Połączono z ClickUp')
if (error === 'no_code') toast.error('Anulowano autoryzację.')
if (error === 'invalid_state') toast.error('Błąd bezpieczeństwa. Spróbuj ponownie.')
if (error === 'auth_failed') toast.error('Autoryzacja ClickUp nie powiodła się.')

router.replace({ query: {} })
```

## Security

- `state` is a 40-character random string stored server-side in the session — standard CSRF protection for OAuth
- Access token encrypted at rest via `'api_token' => 'encrypted'` cast (unchanged)
- Token removed from serialization via `$hidden` (unchanged)
- OAuth routes protected by session `auth` middleware — unauthenticated users cannot initiate or complete the flow

## Testing

### New: `tests/Feature/MorningHub/ClickUpOAuthTest.php`

- Redirect generates a valid ClickUp authorization URL containing `state`, `client_id`, and `redirect_uri`
- Redirect stores `state` and `name` in session
- Callback with valid `code` and matching `state` creates a `ClickUpConnection` and redirects with `?connected=1`
- Callback with mismatched `state` redirects with `?error=invalid_state`
- Callback without `code` redirects with `?error=no_code`
- Callback with failed token exchange (Http::fake) redirects with `?error=auth_failed`

### Updated: `tests/Feature/Api/ClickUpConnectionTest.php`

- Remove all tests for `POST /morning-hub/clickup/connections` (store via token)
- Remove `api_token` assertions from update tests

### Unchanged

- `tests/Unit/ClickUpServiceTest.php`
- `tests/Feature/Api/ClickUpApiTest.php`
