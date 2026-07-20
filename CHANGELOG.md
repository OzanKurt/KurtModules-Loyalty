# Changelog

All notable changes to `ozankurt/laravel-modules-loyalty` will be documented in this file.

## Unreleased

### Security
- **Split card identifiers.** The public URL/QR now uses a long 128-bit `token` (hard to harvest or enumerate), while a short human-typeable `code` is used for counter/manual lookup at the staff-gated terminal. Sharing a card link stays frictionless; the shareable value is no longer a short, guessable string.
- **One-time claim.** In `anonymous_claimable` mode a card can no longer be re-claimed once an identity is attached, so knowing a token can't be used to hijack an already-claimed card (`CardAlreadyClaimedException`, HTTP 409).

### Added
- **M1 — Foundation:** package scaffold, `loyalty_*` schema (programs, cards, vouchers, stamps, redemptions, wallet passes/registrations), models + factories, `LoyaltyCustomer` contract + `IsLoyaltyCustomer` trait, and the domain services: `CardService` (3 identity modes + claim), `VoucherService` (single-use issue/redeem), `StampService` (cooldown / daily-cap / idempotency / threshold-crossing completion), `RedemptionService` (reset or rollover). Eight domain events, no default listeners.
- **M2 — HTTP surface:** public card page + `/state` JSON, card creation, claim, voucher redemption, and a staff terminal (`stamp` / `redeem`) behind the deny-by-default `loyalty:staff` gate, with rate limiting on public writes.
- **M3 — Frontend:** framework-free JS behavior layer keyed to a stable `data-*` contract, Vite build (committed `dist`), contract stylesheet + `coffee`/`hotdog`/`restaurant`/`minimal` themes (light + dark) with a CSS-mask icon system, server-side inline-SVG QR codes (`bacon/bacon-qr-code`, no JS required), and a polling live-update channel. Vitest unit tests.
- **M4 — Wallet & commands:** `WalletProvider` seam with Apple (`.pkpass`) and Google (signed save-link JWT) providers, add-to-wallet endpoints + conditional buttons, opt-in live-push seam wired to card events, and the `loyalty:install` / `loyalty:demo` / `loyalty:prune-vouchers` / `loyalty:wallet-check` commands.
