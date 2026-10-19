# AUTH-001 Phone + Password Authentication Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Bearer-token auth API (register/login/refresh/logout/me) under `/api/v1/auth` with Sanctum 30-minute access tokens, rotating hashed refresh tokens, rate limiting, and a uniform error shape — plus an `is_admin` flag with an admin middleware.

**Architecture:** Controller (JSON only) → FormRequest (validation + phone normalization) → Service (AuthService, TokenService; no request objects) → Repository (only layer touching Eloquent) via interfaces bound in AppServiceProvider. DTOs carry validated data. A single exception renderer in `bootstrap/app.php` outputs `{ "error": { code, message, details } }`.

**Tech Stack:** Laravel 13.37, Sanctum 4.3, PHPUnit 12.5 (project has no Pest; BOOST rules require PHPUnit — deviation from ticket noted), PHP 8.5, sqlite in-memory for tests.

**Spec:** `docs/superpowers/plans/` companion — the AUTH-001 ticket text as provided in this session (assumptions approved; user added: include `is_admin` and its middleware).

## Global Constraints

- Phone normalized to `09xxxxxxxxx`; accepts Persian/Arabic digits, `+98`, `0098`, `98`, `9xxxxxxxxx`.
- national_id: exactly 10 digits, valid Iranian checksum, reject all-same-digit codes.
- gender: `male`|`female`; password min 8 chars, confirmed on register.
- Access token TTL: 30 min (`config/sanctum.php` `expiration => 30`). Refresh token: random 48 bytes hex (`Str::random(64)`-style ≥64 chars), SHA-256 hashed in DB, 30-day expiry.
- Codes: `INVALID_CREDENTIALS`(401), `UNAUTHENTICATED`(401), `REFRESH_TOKEN_INVALID`(401), `TOO_MANY_REQUESTS`(429), `VALIDATION_FAILED`(422), `FORBIDDEN`(403).
- Rate limits: login 5/min per phone+IP; register 5/min per IP; refresh 10/min per IP; authenticated 60/min.
- Never log passwords/tokens. Login uses `hash()->check` against a dummy hash when phone is unknown.
- Routes `api/v1/auth/*` in new `routes/api.php` (register via `withRouting(api:)`).
- No comments in new code unless asked; run `vendor/bin/pint --dirty --format agent` at the end of each code task.
- `EnsureUserIsAdmin` switches from `tokenCan('is_admin')` to `$user->is_admin` (ticket: no token abilities); unmatched requests: 401 without user, else `FORBIDDEN` 403.

## Review Focus

- Reuse of a rotated refresh token must nuke the whole chain, not just fail (test: RefreshTest).
- Expired access token must yield `UNAUTHENTICATED` JSON, never a redirect (test: MeTest).
- Wrong password vs unknown phone must be indistinguishable (test: LoginTest).
- Persian-digit / `+98` phones must all land as the same stored value (test: PhoneNormalizationTest).
- All-same-digit national id (e.g. `1111111111`) fails checksum (test: NationalIdValidationTest).

---

### Task 1: Phone + national-id helpers

**Files:**
- Create: `app/Support/PhoneNormalizer.php`, `app/Support/NationalId.php`
- Test: `tests/Unit/PhoneNormalizerTest.php`, `tests/Unit/NationalIdTest.php`

**Interfaces:**
- Consumes: nothing.
- Produces: `App\Support\PhoneNormalizer::normalize(?string $raw): ?string` (null for empty/invalid-charset; digits mapped, prefix `+98`/`0098`/`98` stripped, leading `9` gains `0`). `App\Support\NationalId::validate(?string $raw): bool` (true iff 10 digits, not all identical, checksum passes; ignores non-digits → false).

- [ ] **Step 1: Failing unit tests** — Persian digits `۰۹۱۲۳۴۵۶۷۸۹`→`0912345678901`-style mapping (use `۰۹۱۲` `۳۴۵` `۶۷۸۹` string with 11 digits, e.g. Persian `۰۹۱۲۳۴۵۶۷۸۹۰` → `091234567890` + check 12-digit input returns it unchanged? Plan: test cases: `'09123456789'`→self; Persian equivalent; Arabic-Indic equivalent; `'+989123456789'`→`09123456789`; `'00989123456789'`; `'9123456789'`; `'0912 345 6789'`(spaces stripped); `'123'`→null; `'0912345678'`(10 digits)→`0912345678` accepted as-is if starts with 09? — decision: output must be 11 digits starting `09…`; 12/14-char inputs that normalize to 11 are accepted).
- NationalId valid sample `0079926413`? Use known-valid `0468534479`. Invalid checksum, 9 digits, non-digits, `1111111111` → false.
- [ ] **Step 2: Run, watch fail** `vendor/bin/phpunit tests/Unit/PhoneNormalizerTest.php tests/Unit/NationalIdTest.php`.
- [ ] **Step 3: Implement** pure static methods; digit map via `strtr` over `۰۱۲۳۴۵۶۷۸۹۰۱۲۳۴۵۶۷۸۹` (Persian+Arabic); strip `[\s\-]`, `+98`, `0098`, `98` prefix when length suggests, prefix `0` if starts `9` and length 10; final assert 11 digits `09…` else null.
- [ ] **Step 4: Run, watch pass.**

### Task 2: Migrations, models, Sanctum config

**Files:**
- Create: `database/migrations/xxxx_add_auth_columns_to_users_table.php` (add `password` + `is_admin` boolean default false after `national_id`)
- Modify: `app/Models/User.php` (fillable extends `password`, `is_admin`; `is_admin` bool cast; `password` stays `hashed` cast? — with attribute fillable style, add `#[Fillable([...])]` update matching existing pattern)
- Create: `database/migrations/xxxx_create_refresh_tokens_table.php`: `id`, `ulid-chain_id` (string `chain_id`, index), `user_id` FK cascade, `access_token_id` FK to `personal_access_tokens` id, cascade, `token_hash` string unique, `expires_at`, `revoked_at` nullable, `timestamps`
- Create: `app/Models/RefreshToken.php` (guarded `-`? follow User style: use `Fillable` attribute; casts `expires_at:datetime`, `revoked_at:datetime`; belongsto `access_token` → `Laravel\Sanctum\PersonalAccessToken`, `user`)
- Modify: `config/sanctum.php` `expiration => 30`
- Modify: `database/factories/UserFactory.php` (`password` default `Hash::make('password')`-style via `hashed` cast with plain `'password'`? simplest: `'password' => null` not allowed; use `'password' => 'password'` relying on `hashed` cast is NOT reliable in factory — set `'password' => Hash::make('password')`; add state `admin()`)

- [ ] **Step 1: Failing factory/migration smoke check**: `php artisan migrate:fresh` in a sqlite test env run by phpunit later; write no test yet — verified by Task 4+ feature tests and `php artisan migrate` (run once).
- [ ] **Step 2: Implement migrations/model/factory/config.**
- [ ] **Step 3: Run `php artisan migrate:fresh --no-interaction`** → PASS; `php artisan test --compact tests/Feature/CookieTokenAuthTest.php` still green.
- [ ] **Step 4: Commit**

### Task 3: Exceptions + error shape

**Files:**
- Create: `app/Exceptions/ApiException.php` (`__construct(public string $errorCode, string $message, int $status = 400, public ?array $details = null)`; extends `RuntimeException`)
- Create: `app/Exceptions/InvalidCredentialsException.php` (`INVALID_CREDENTIALS`, 401, "phone or password is incorrect")
- Create: `app/Exceptions/UnauthenticatedException.php` (`UNAUTHENTICATED`, 401, "unauthenticated")
- Create: `app/Exceptions/RefreshTokenInvalidException.php` (`REFRESH_TOKEN_INVALID`, 401, "refresh token is invalid")
- Create: `app/Exceptions/TooManyRequestsException.php` (`TOO_MANY_REQUESTS`, 429, "too many requests")
- Create: `app/Exceptions/ForbiddenException.php` (`FORBIDDEN`, 403, "forbidden")
- Modify: `bootstrap/app.php` (`$exceptions->render(...)`): ApiException → JSON `{error:{code,message,details}}`; `ValidationException` → 422 code `VALIDATION_FAILED`, `details` = `$e->errors()`; `AuthenticationException` → `UnauthenticatedException` shape; `ThrottleRequestsException` → 429 + `Retry-After` header; HttpException 403 → FORBIDDEN; fallback for `api/*` → 500 `SERVER_ERROR` (no trace).

**Interfaces:**
- Produces: every later task throws `ApiException` subclasses and gets the exact JSON shape.

- [ ] **Step 1: Failing test** `tests/Feature/ErrorHandlingTest.php`: register with invalid payload → 422 body `error.code = VALIDATION_FAILED` with field keys inside `error.details`; then temporary test throwing `InvalidCredentialsException` via a throwaway route → 401 `INVALID_CREDENTIALS`; unauthenticated request → `UNAUTHENTICATED`.
- [ ] **Step 2: watch fail → implement → pass → commit.**

### Task 4: Repositories, DTOs, TokenService

**Files:**
- Create: `app/Repositories/Contracts/UserRepositoryInterface.php` — `findByPhone(string $phone): ?User`, `create(RegisterData $data): User`
- Create: `app/Repositories/Contracts/RefreshTokenRepositoryInterface.php` — `findByPlainToken(string $plain): ?RefreshToken` (hashes + queries; returns also preloaded relation access_token), `create(RefreshTokenData $data): RefreshToken`, `revoke(RefreshToken $token): void`, `revokeChain(string $chainId): void`, `revokeForAccess(int $accessTokenId): void`, `chainIdOf(RefreshToken $token): string`
- Create: `app/Repositories/UserRepository.php`, `app/Repositories/RefreshTokenRepository.php` (implements, Eloquent; hashing helper `hashToken(): string` sha256)
- Create: `app/DataTransferObjects/RegisterData.php` (`phone, firstname, lastname, gender, nationalId, password` — readonly promoted), `LoginData.php` (`phone, password`), `RefreshTokenData.php` (`userId, chainId, accessTokenId`), `TokenPair.php` (`public string $accessToken; public string $refreshToken; public Carbon $accessTokenExpiresAt; public Carbon $refreshTokenExpiresAt; public int $accessId`)
- Bind interfaces in `AppServiceProvider::register()`.
- Modify `AppServiceProvider::boot()`: `RateLimiter::for('login')` (5/min per `phone|ip`), `register` (5/min ip), `refresh` (10/min ip), `api` (60/min user or ip) — exact names as keys.

- [ ] **Step 1: Failing test** `tests/Unit/TokenServiceTest.php`: issue → non-empty plain values stored hashed in DB (assert stored `token_hash` ≠ plain, sha256 of plain); rotate(previousPlain) → new pair, old row `revoked_at` set, old access token deleted, chain_id preserved; reuse previousPlain after rotation → `RefreshTokenInvalidException` thrown AND chain revoked (all chain rows revoked).
- [ ] **Step 2: implement TokenService (`app/Services/TokenService.php`)** — `issue(User $user): TokenPair` (chain `Str::uuid()`), `rotate(string $plain): TokenPair` (lookup by hash; refund messages: unknown/expired/revoked → `RefreshTokenInvalidException`; if found revoked → treat as reuse: `revokeChain` then throw; success: revoke row + delete access token, `issue` with same chainId), `revokeByRefresh(string $plain, string $chainId): void` for logout.
- [ ] **Step 3: watch fail → pass → commit.**

### Task 5: AuthService + UserRepository behavior

**Files:**
- Create: `app/Services/AuthService.php` — `register(RegisterData $d): TokenPair`, `login(LoginData $d): TokenPair`, `logout(User $user): void`

- [ ] **Step 1: Failing unit test** `tests/Unit/AuthServiceTest.php`: login unknown phone raises `InvalidCredentialsException`; wrong password same exception class + same message; register creates user with normalized values and calls TokenService::issue. (Unknown-phone test asserts a dummy hash check happened? — skip timing; assert same exception/message as wrong-password by comparing `getMessage()`.)
- [ ] **Step 2: implement** (`login`: fetch repo; `Hash::check($password, $found ? $found->password : Hash::make('x'))`; guard `is_admin` untouched). `logout`: `TokenService::revokeByRefresh` not possible from user alone → logout uses current access token id `$user->currentAccessToken()->id` → repo `revokeForAccess`.
- [ ] **Step 3: pass → commit.**

### Task 6: Form Requests + UserResource + AuthController + routes

**Files:**
- Create: `app/Http/Requests/Auth/RegisterRequest.php` (`prepareForValidation` normalizes phone & national_id; rules: phone `required|string|min:10|max:16` then post-normalize `regex:^09\d{9}$`? — decision: normalize first, then `required|regex:/^09\d{9}$/|unique:users,phone`; `national_id` 10 digits + unique + custom closure rule `NationalId::validate`; `gender in:male,female`; `firstname/lastname min:2`; `password min:8 confirmed`)
- Create: `app/Http/Requests/Auth/LoginRequest.php` (phone normalize + regex; `password required|string`)
- Create: `app/Http/Requests/Auth/RefreshRequest.php` (`refresh_token required|string`)
- Create: `app/Http/Resources/UserResource.php` (id, phone, firstname, lastname, gender, national_id)
- Create: `app/Http/Controllers/Auth/AuthController.php` — `register(RegisterRequest, AuthService, TokenService): JsonResponse 201`, `login(LoginRequest) 200`, `refresh(RefreshRequest) 200`, `logout(Request) 200 {"data":null->? decision: {"message":"logged out"}`? keep `{"data": true}`? — decision: `logout` returns `{"data": null}` per minimal), `me(Request): UserResource` (`{"data": {...}}` request->user())
- Create: `routes/api.php` — `Route::prefix('auth')->group`: `register` (middleware `throttle:register`), `login` (`throttle:login`), `refresh` (`throttle:refresh`), group `middleware:['auth:sanctum','throttle:api']`: `logout`, `me`; admin demo route not needed here.
- Modify: `bootstrap/app.php` add `api: __DIR__.'/../routes/api.php'` to `withRouting`.
- Modify: `app/Http/Middleware/EnsureUserIsAdmin.php` — replace `tokenCan('is_admin')` with `$user->is_admin`; keep 401 throw when no user; replace `abort_unless` with `throw new ForbiddenException(...)`.
- Modify: `tests/Feature/CookieTokenAuthTest.php` admin route test → user factory state `admin` (or `->update(['is_admin' => true])`), plus non-admin assert 403 `FORBIDDEN`.

**Interfaces:**
- Consumes: AuthService/TokenService from Tasks 4–5, exceptions Task 3, helpers Task 1.
- Produces: endpoints `/api/v1/auth/*` final JSON.

- [ ] **Step 1: Failing feature tests** `tests/Feature/Auth/RegisterTest.php`, `LoginTest.php`, `MeTest.php`, `RefreshTest.php`, `LogoutTest.php`:
  - Register: valid → 201, shape `{data:{user:{…},access_token,access_token_expires_at,refresh_token,refresh_token_expires_at,token_type:"Bearer"}}`, persisted with normalized phone; duplicate phone/national_id → 422 `VALIDATION_FAILED`; invalid national checksum → 422; `is_admin` not settable (mass assignment test: sending `is_admin` ignored).
  - Login: success → 200 same shape; wrong password & unknown phone → identical 401 body `INVALID_CREDENTIALS`.
  - Me: valid token → user without password; no token → 401 `UNAUTHENTICATED`; expired token → travel 40 min → 401 `UNAUTHENTICATED`; revoked token → 401.
  - Refresh: rotate → new refresh works, old reuse → 401 `REFRESH_TOKEN_INVALID` AND the new (rotated) token also invalidated (chain revoked); expired (31 days) → 401.
  - Logout: device A logout (revokes A access+refresh); device B token still 200 on `me`; A token now 401.
  - Admin: middleware `admin` on a test route: non-admin → 403 `FORBIDDEN`, admin → 200, unauthenticated → 401 `UNAUTHENTICATED`.
- [ ] **Step 2: watch fail → implement (order: requests, resource, controller, routes, middleware) → pass → commit.**
- [ ] **Step 3: Rate limiting** `tests/Feature/Auth/RateLimitTest.php`: 6th login attempt in a minute → 429 body `error.code=TOO_MANY_REQUESTS`, response has `Retry-After`; different phone not blocked (same IP). implement → pass → commit.

### Task 7: Finalization

- [ ] **Step 1:** `vendor/bin/pint --dirty --format agent`
- [ ] **Step 2:** `vendor/bin/phpunit` full suite green
- [ ] **Step 3:** Append short auth section to `AGENTS.md`: endpoints, token lifetimes, error code list, `admin` middleware semantics.

## Self-review notes

- Spec coverage: register/login/refresh/logout/me ✔ (T6), 30min token ✔ (T2 config), refresh rotation+reuse ✔ (T4), hashed storage ✔ (T4), rate limits ✔ (T4/T6), error shape ✔ (T3), dummy-hash anti-enumeration ✔ (T5), JSON 401 ✔ (T3/`auth:sanctum` default JSON rendering via shouldRenderJsonWhen), is_admin+middleware ✔ (T2/T6), no business logic in controllers / Eloquent only in repos ✔ by layering, Pest→PHPUnit deviation noted.
- Type consistency: `TokenPair` has `int $accessId` needed by nothing otherwise; rotate keeps `chain_id`.
