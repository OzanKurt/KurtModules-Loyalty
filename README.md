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

## Status

- **Milestone 1 (this branch):** package foundation, schema, models/factories, and the four domain services (Card, Voucher, Stamp, Redemption). ✅
- **Milestone 2:** HTTP surface (customer routes, `/state`, voucher redeem, staff terminal).
- **Milestone 3:** Blade + vanilla JS + Vite build + themes.
- **Milestone 4:** Apple/Google Wallet providers, live push, artisan commands.

See [`docs/superpowers/specs`](docs/superpowers/specs) for the full design.

## License

MIT © [Ozan Kurt](mailto:ozankurt2@gmail.com)
