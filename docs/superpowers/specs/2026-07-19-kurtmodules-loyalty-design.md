# KurtModules-Loyalty — Design Spec

**Date:** 2026-07-19
**Package:** `ozankurt/laravel-modules-loyalty`
**Namespace:** `Kurt\Modules\Loyalty\` → `src/`
**Table prefix:** `loyalty_`
**Status:** Approved design, pre-implementation

Inspired by [card.marsespresso.com](https://card.marsespresso.com/) — a digital coffee stamp card: collect N stamps, get a free drink, with a shareable card URL, a QR code, and (as an extension) Apple/Google Wallet passes.

---

## 1. Purpose & scope

A reusable, customer-facing digital loyalty / stamp-card package for Laravel apps. One install can run **many programs** ("buy 7 coffees → 1 free", "buy 5 haircuts → 1 free"), each optionally owned by a consumer-defined entity (cafe, location, tenant).

**Ships the full experience end to end:**
- Public, themeable card page (progress + QR).
- Stamp granting + reward redemption with anti-fraud guards.
- Apple Wallet + Google Wallet passes, live-updating (opt-in).
- A staff "terminal" screen for granting stamps / redeeming.

### Deliberate departures from the KurtModules family baseline
The rest of the family is **headless + Filament-only, no public views**. This module is the opposite by design (approved): it is a **customer surface**. It therefore ships **public routes, Blade views, and compiled JS/CSS assets, and NO Filament panel**. It still `require`s Core and follows every other convention (namespace, provider base, migrations, testing, CI, versioning). This departure is called out in the README.

### Out of scope (v1)
- Points/tiers/spend-based programs (this is stamp-card only; `stamps_required` is the sole progression axis).
- Shipping Apple/Google credentials (consumer supplies certs/service account).
- A staff admin CRUD panel (program management is the consumer app's job; `loyalty:demo` + factories cover seeding).

---

## 2. Core principle — headless behavior, swappable skin

**One JS behavior layer, unlimited CSS.** JS and CSS are fully decoupled through a **stable data-attribute contract**. Swapping the entire stylesheet must never break behavior; the JS assumes no class names and no colors.

### Data-attribute contract (stable public API)
Customer card page:
- `[data-loyalty-card]` (root, carries token)
- `[data-loyalty-stamps]` (container) → `[data-loyalty-stamp data-state="filled|empty|reward"]`
- `[data-loyalty-progress]`, `[data-loyalty-count]`, `[data-loyalty-required]`
- `[data-loyalty-qr]`
- `[data-loyalty-wallet="apple|google"]`
- `[data-loyalty-create]`, `[data-loyalty-claim]`

Staff terminal:
- `[data-loyalty-scanner]`, `[data-loyalty-stamp-btn]`, `[data-loyalty-redeem-btn]`

Config is delivered to JS via `<script type="application/json" data-loyalty-config>…</script>` (token, state endpoint, poll interval, wallet availability, transport). JS reads this once and never depends on markup structure beyond the data hooks — consumers may rewrite the Blade freely as long as hooks survive.

### JS layer (pure vanilla, Vite-built)
- ES modules, **zero framework**.
- Responsibilities: fetch/poll card state; animate stamp transitions by toggling `data-state` (CSS owns the visuals); render QR; wire wallet buttons; run the staff scanner via `BarcodeDetector` with a `getUserMedia` + decode fallback.
- **Live updates:** default **polling** of `/c/{token}/state` (dependency-free, matches "pure JS"). Optional **broadcast driver** (Laravel Echo) when the app already runs Reverb/Pusher. Both sit behind one `LoyaltyChannel` interface; transport chosen by config.

### Theming (CSS packs, fully overridable)
- A tiny **contract stylesheet** of CSS custom properties: `--loyalty-bg`, `--loyalty-surface`, `--loyalty-accent`, `--loyalty-stamp-size`, `--loyalty-radius`, etc.
- Several **presets**, each with **light + dark** (`prefers-color-scheme` default + `:root[data-theme="dark|light"]` override): `coffee`, `hotdog`, `restaurant`, `minimal`.
- **Icon system:** the stamp glyph is themeable via an SVG sprite (coffee cup, hotdog, fork/knife, star, generic stamp) + a per-program `icon` config; a theme can override the glyph in pure CSS (`mask` / `<use href>`).
- Consumers pick a preset in config, edit a published copy, or ship their own stylesheet — the JS is identical in every case.

### Asset delivery (Vite)
- Vite emits a prebuilt `dist` so the module works with **zero build**: `php artisan vendor:publish --tag=loyalty-assets`.
- Source (`resources/js`, `resources/css/themes`) + a Vite preset ship too, so consumers can import, extend, and rebuild within their own pipeline.

---

## 3. Data model (`loyalty_` tables)

All tables: `bigIncrements` id, `created_at`/`updated_at`, `softDeletes()` on domain rows (not on the immutable `stamps` log).

### `loyalty_programs`
`name` (json/translatable), `slug`, `reward` (json/translatable), `stamps_required` (int), `theme` (string), `icon` (string), `stamp_cooldown_seconds` (int), `max_stamps_per_day` (int, nullable), `reset_on_reward` (bool, default true), nullable polymorphic `owner` (`owner_type`/`owner_id`), `is_active` (bool).

### `loyalty_cards`
`program_id` FK, `token` (random, unique, indexed — the public URL id), identity: nullable `user_id` (via `config('auth.providers.users.table')`) + nullable `email` + nullable `phone` (claim), denormalized `stamps_count`, `rewards_earned`, `rewards_redeemed`, `status`, `last_stamped_at`. Identity mode enforced by config.

### `loyalty_stamps` (immutable log)
`card_id` FK, nullable `voucher_id`, `source` enum (`staff_terminal|receipt|till_qr|api|manual`), nullable `granted_by` (morph or string), `created_at`. No soft delete.

### `loyalty_vouchers` (unified single-use grant primitive)
`program_id` FK, `token` (signed/random), `stamps` (int, default 1), nullable `issued_by`, nullable `expires_at`, nullable `redeemed_at`, nullable `redeemed_by_card_id`, `status`. Staff terminal, receipt print, and till QR all just **issue one of these**; redemption is one code path.

### `loyalty_redemptions` (reward claim log)
`card_id` FK, `reward` snapshot (json), nullable `redeemed_by`, `created_at`.

### `loyalty_wallet_passes` + `loyalty_wallet_registrations`
`wallet_passes`: `card_id`, `platform` enum (`apple|google`), `external_id` (serial / object id), nullable `auth_token`, `last_pushed_at`.
`wallet_registrations` (Apple live push): `device_library_id`, `push_token`, pass serial. Enables APNs updates.

---

## 4. Domain services

- **CardService** — create card honoring identity mode (`anonymous` / `user` / `anonymous_claimable`); `claim()` attaches email/phone/user to an anonymous card.
- **VoucherService** — `issue()` a single-use voucher (terminal / receipt / till); `redeem(token, card)` atomically enforces expiry + single-use, then hands to StampService.
- **StampService** — the guarded write. Within a DB transaction: enforce **cooldown** and **per-day cap**, be **idempotent per voucher**, append a `stamps` row, update denormalized counters, detect completion, fire events, trigger wallet push.
- **RedemptionService** — on a completed card: log a `redemption`, increment `rewards_redeemed`, reset stamps (or roll over per `reset_on_reward`), push wallet update.

All state-changing actions dispatch a domain event; the module ships **no default listeners**.

### Events
`CardCreated`, `CardClaimed`, `VoucherIssued`, `VoucherRedeemed`, `StampAdded`, `CardCompleted`, `RewardRedeemed`, `WalletPassIssued`, `WalletPassUpdated`.

---

## 5. HTTP surface (prefix/domain configurable, default `/loyalty`)

**Customer**
- `GET /c/{token}` — themed card page (Blade + assets)
- `GET /c/{token}/state` — JSON state for JS poll/live
- `POST /programs/{program}/cards` — create a card
- `POST /c/{token}/claim` — attach email/phone/user
- `GET /c/{token}/apple-pass` — download `.pkpass`
- `GET /c/{token}/google-pass` — "Add to Google Wallet" link/JWT

**Receipt / till redemption**
- `GET /v/{token}` — customer scans a voucher QR; redeems onto their card (prompts create/pick if none)

**Staff terminal** (behind the `loyalty:staff` Gate — consumer-defined, default deny)
- `GET /terminal` — scanner UI
- `POST /terminal/stamp` — issue+redeem a stamp onto a scanned card token
- `POST /terminal/redeem` — redeem a reward on a scanned card token

**Apple Wallet web service**
- `/apple/v1/...` — device registration, latest pass, log endpoints (live push)

**Security:** public write routes get **rate-limiting middleware**; voucher and claim tokens are signed; the staff Gate defaults to deny so an unconfigured install can't be abused.

---

## 6. Wallet (adapter seam — both platforms, live)

- **`WalletProvider` contract:** `buildPass(Card): PassPayload`, `serialFor(Card): string`, `pushUpdate(Card): void`.
- **AppleWalletProvider** — builds `.pkpass` (zip + PKCS7 signing via `ext-openssl`; cert / passTypeId / teamId from config). Live updates via the Apple Wallet web service + **APNs** push to registered devices.
- **GoogleWalletProvider** — Loyalty class/object + signed **"Add to Google Wallet"** JWT (`firebase/php-jwt`); live updates via object patch (`google/apiclient`).
- **Opt-in per install:** if certs / service account are absent, wallet buttons don't render. `loyalty.wallet.push` toggles live vs static. Live push requires a **queue worker**.

---

## 7. Package wiring & config

Provider extends Core's `PackageServiceProvider`:
```php
$package->name('laravel-modules-loyalty')
    ->hasConfigFile()
    ->hasTranslations()
    ->hasViews()
    ->hasMigrations([...])
    ->hasRoutes(['loyalty'])   // web + apple web service
    ->hasAssets()
    ->hasCommands([...]);
```
**No Filament sub-provider.**

**Commands:** `loyalty:install` (publish config/views/assets/migrations), `loyalty:prune-vouchers` (expire stale), `loyalty:demo` (seed a demo program + card for instant payoff), `loyalty:wallet-check` (validate cert/service-account config).

**`config/loyalty.php`:** identity mode; route `prefix`/`domain`; staff gate name; `stamp_cooldown_seconds` + `max_stamps_per_day`; `reset_on_reward`; default `theme` + `icon`; `transport` (poll|broadcast) + poll interval; `wallet` block (apple/google enable, push, cert paths, passTypeId, teamId, google issuer id, class id, service account path); assets strategy.

**User integration:** never references `App\Models\User`; resolves via Core's `UserResolver` / `ResolvesUser`. Exposes a `LoyaltyCustomer` contract + `IsLoyaltyCustomer` trait for user-bound mode.

**Authorization:** Gates only (`loyalty:staff`), no `spatie/laravel-permission`.

---

## 8. Testing (Pest 3, ≥80% lines, family baseline)

Base test case extends Core's `PackageTestCase` (sqlite `:memory:`).

**Feature**
- Card creation across all three identity modes.
- Stamp **cooldown** and **daily-cap** rejection paths.
- Voucher **single-use** + **expiry** + **idempotency**.
- Reward completion → redeem → reset / rollover (`reset_on_reward` both ways).
- `loyalty:staff` Gate enforcement (deny by default; allow when bound).
- Public route rate-limits.
- Event dispatch via `Event::fake()` (never hits a live broadcaster/APNs/Google).

**Unit**
- Apple/Google pass payload builders with fake certs.
- `/state` JSON shape.

**Frontend**
- Light **Vitest** unit tests for the data-attribute state machine (stamp add/remove, completion). Optional but recommended — the JS is the product. PHP coverage gate stays authoritative at 80%.

---

## 9. Risks & explicit calls made

- **Convention departure** (public views + assets, no Filament) — intentional, per direction; documented in README.
- **Wallet certs are the consumer's** — the package can't bundle Apple/Google credentials; live push needs APNs + a queue worker.
- **Live updates default to polling** — dependency-free, matches "pure JS"; broadcast is an opt-in driver.
- **Spec location** — the family keeps per-module specs in the Blog repo; this one lives in its own repo (`KurtModules-Loyalty`) because implementation happens here, per direction.

---

## 10. Build order (for the implementation plan)

1. Scaffold package skeleton (composer, provider, config, CI, Core dep, test base).
2. Migrations + models + factories + enums + counters.
3. Domain services (Card, Voucher, Stamp, Redemption) + events + guards. **TDD.**
4. HTTP surface: customer routes/controllers + `/state` + voucher redeem + staff terminal + Gate.
5. Frontend: Blade views (data-attribute contract) + vanilla JS + Vite build + contract stylesheet.
6. Themes (coffee/hotdog/restaurant/minimal, light+dark) + icon sprite.
7. Wallet adapter + Apple provider + Google provider + web service endpoints + push.
8. Commands (`install`, `demo`, `prune-vouchers`, `wallet-check`).
9. Docs (README with the convention-departure note), UPGRADE/CHANGELOG, polish, coverage to ≥80%.
