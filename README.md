# KurtModules Loyalty

Digital loyalty / stamp-card system for Laravel apps — collect stamps, earn rewards, redeem them, with a public card page, QR-based granting, and Apple/Google Wallet passes.

Part of the [KurtModules](https://github.com/OzanKurt) family. Requires `ozankurt/laravel-modules-core`.

## A deliberate departure from the family

Every other KurtModules package is headless + Filament-only with no public views. **Loyalty is the exception by design:** it is a *customer-facing* surface, so it ships public routes, Blade views, and compiled JS/CSS assets, and **no Filament panel**. It still follows every other family convention (Core-based provider, anonymous publishable migrations, Pest + Testbench, SemVer).

## Architecture

- **Headless behavior, swappable skin.** One vanilla-JS behavior layer keyed to a stable `data-*` attribute contract; the entire stylesheet is replaceable without touching behavior. Ships `coffee`, `hotdog`, `restaurant`, and `minimal` themes (light + dark), all overridable.
- **Unified voucher primitive.** Every stamp is granted by redeeming a single-use `Voucher` — whether issued by a staff terminal, printed on a receipt, or shown as a till QR.
- **Configurable identity.** Cards can be anonymous (Mars-style), user-bound, or anonymous-then-claimable — chosen per install.
- **Wallet via an adapter seam.** Apple `.pkpass` + Google Wallet behind one `WalletProvider` interface; live-updating passes are opt-in (certs/service account are the consuming app's to supply).

## Status — all milestones landed ✅

- **M1** — package foundation, schema, models/factories, domain services (Card, Voucher, Stamp, Redemption).
- **M2** — HTTP surface: customer card routes, `/state` JSON, voucher redemption, staff terminal behind the `loyalty:staff` gate, rate limiting.
- **M3** — frontend: vanilla-JS behavior layer (data-attribute contract), Vite build with committed `dist`, contract stylesheet + `coffee`/`hotdog`/`restaurant`/`minimal` themes (light + dark), QR rendering.
- **M4** — Apple `.pkpass` + Google Wallet providers behind a `WalletProvider` seam, add-to-wallet endpoints + buttons, live-push seam (opt-in), and artisan commands (`loyalty:install`, `loyalty:demo`, `loyalty:prune-vouchers`, `loyalty:wallet-check`).

## Quick start

```bash
composer require ozankurt/laravel-modules-loyalty
php artisan loyalty:install   # publish config, migrations, views, assets
php artisan migrate
php artisan loyalty:demo       # seed a program + card, prints the card URL
```

Define who staff are (the terminal is deny-all until you do):

```php
// AuthServiceProvider
Gate::define('loyalty:staff', fn ($user) => $user->is_staff);
```

Wallet passes are opt-in — set the Apple certificate / Google service-account env vars, then `php artisan loyalty:wallet-check`. Live pass updates need `LOYALTY_WALLET_PUSH=true` plus a queue worker.

## Frontend

The behavior layer is framework-free and keyed to a stable `data-*` attribute contract, so the entire stylesheet is swappable without touching JS. Rebuild/extend it with your own pipeline:

```bash
npm install
npm run build   # -> resources/dist (loyalty.js, loyalty.css, themes/*)
npm run test    # Vitest
```

See [`docs/superpowers/specs`](docs/superpowers/specs) for the full design and [`docs/superpowers/plans`](docs/superpowers/plans) for the build plan.

## License

MIT © [Ozan Kurt](mailto:ozankurt2@gmail.com)
