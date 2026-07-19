# Loyalty Module — Milestone 1: Foundation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Stand up the `ozankurt/laravel-modules-loyalty` package skeleton and its domain core — data model, models/factories, and the four guarded domain services (Card, Voucher, Stamp, Redemption) — fully test-driven and green.

**Architecture:** Headless Laravel package following the KurtModules family baseline (Spatie package-tools provider extending Core's `PackageServiceProvider`, anonymous publishable migrations, Pest + Testbench sqlite `:memory:`). The domain is the unified-voucher model: every stamp is granted by redeeming a single-use `Voucher`; `StampService` is the only guarded write path (cooldown, daily cap, idempotency). This milestone ships **no HTTP, no views, no wallet** — those are Milestones 2–4.

**Tech Stack:** PHP 8.4 (`C:/laragon/bin/php/php-8.4.5-nts-Win32-vs17-x64/php.exe`), composer `C:/laragon/bin/composer/composer.phar`, Laravel 12/13 (illuminate ^12|^13), `ozankurt/laravel-modules-core ^2.0` (VCS), `spatie/laravel-package-tools`, `spatie/laravel-translatable`, Pest 3 + Testbench.

**Conventions:** Namespace `Kurt\Modules\Loyalty\` → `src/`. Table prefix `loyalty_`. Commits: no AI attribution (family rule). Run everything through the 8.4 binary. No pcov locally → run `pest` without `--coverage`.

---

## File Structure (Milestone 1)

```
composer.json                          # package manifest, Core via VCS
phpunit.xml.dist  pint.json  phpstan.neon  rector.php
.github/workflows/tests.yml
README.md  LICENSE.md  CHANGELOG.md
config/loyalty.php                     # config surface (M1 uses identity + throttle keys)
src/
  Enums/IdentityMode.php               # anonymous | user | anonymous_claimable
  Enums/CardStatus.php                 # active | completed | disabled
  Enums/StampSource.php                # staff_terminal | receipt | till_qr | api | manual
  Enums/WalletPlatform.php             # apple | google
  Contracts/LoyaltyCustomer.php        # marker for user-bound cards
  Concerns/IsLoyaltyCustomer.php       # trait: cards() relation for the app User
  Models/Program.php  Card.php  Stamp.php  Voucher.php  Redemption.php
  Models/WalletPass.php  WalletRegistration.php
  Exceptions/StampThrottledException.php
  Exceptions/DailyStampLimitReachedException.php
  Exceptions/VoucherAlreadyRedeemedException.php
  Exceptions/VoucherExpiredException.php
  Exceptions/CardNotClaimableException.php
  Events/CardCreated.php  CardClaimed.php  VoucherIssued.php  VoucherRedeemed.php
  Events/StampAdded.php  CardCompleted.php  RewardRedeemed.php
  Services/CardService.php  VoucherService.php  StampService.php  RedemptionService.php
  Providers/LoyaltyServiceProvider.php
database/migrations/                   # 7 anonymous create_* migrations
database/factories/…                   # one per model
tests/Pest.php  tests/TestCase.php
tests/Unit/…  tests/Feature/…
```

---

### Task 1: Package scaffold + green empty suite

**Files:**
- Create: `composer.json`, `phpunit.xml.dist`, `pint.json`, `phpstan.neon`, `rector.php`, `.github/workflows/tests.yml`, `README.md`, `LICENSE.md`, `CHANGELOG.md`
- Create: `src/Providers/LoyaltyServiceProvider.php`, `config/loyalty.php`
- Create: `tests/TestCase.php`, `tests/Pest.php`, `tests/Unit/SanityTest.php`

- [ ] **Step 1: Write `composer.json`** (mirror of Library, loyalty deps, Core via VCS)

```json
{
  "name": "ozankurt/laravel-modules-loyalty",
  "description": "Digital loyalty / stamp-card system for Laravel apps (KurtModules).",
  "keywords": ["laravel", "kurtmodules", "loyalty", "stamp-card", "rewards", "wallet"],
  "license": "MIT",
  "authors": [{ "name": "Ozan Kurt", "email": "ozankurt2@gmail.com" }],
  "require": {
    "php": "^8.3",
    "illuminate/contracts": "^12.0 || ^13.0",
    "illuminate/database": "^12.0 || ^13.0",
    "illuminate/support": "^12.0 || ^13.0",
    "ozankurt/laravel-modules-core": "^2.0",
    "spatie/laravel-package-tools": "^1.92",
    "spatie/laravel-translatable": "^6.11"
  },
  "require-dev": {
    "larastan/larastan": "^3.0",
    "laravel/pint": "^1.18",
    "orchestra/testbench": "^9.0 || ^10.0",
    "pestphp/pest": "^3.0",
    "pestphp/pest-plugin-laravel": "^3.0",
    "rector/rector": "^2.0"
  },
  "autoload": { "psr-4": { "Kurt\\Modules\\Loyalty\\": "src/" } },
  "autoload-dev": {
    "psr-4": {
      "Kurt\\Modules\\Loyalty\\Tests\\": "tests/",
      "Database\\Factories\\Kurt\\Modules\\Loyalty\\": "database/factories/"
    }
  },
  "extra": { "laravel": { "providers": ["Kurt\\Modules\\Loyalty\\Providers\\LoyaltyServiceProvider"] } },
  "repositories": [
    { "type": "vcs", "url": "https://github.com/OzanKurt/KurtModules-Core" }
  ],
  "config": { "sort-packages": true, "allow-plugins": { "pestphp/pest-plugin": true } },
  "scripts": {
    "test": "vendor/bin/pest",
    "lint": "vendor/bin/pint --test",
    "format": "vendor/bin/pint",
    "stan": "vendor/bin/phpstan analyse --memory-limit=2G"
  },
  "minimum-stability": "stable",
  "prefer-stable": true
}
```

- [ ] **Step 2: Copy config/tooling files from Core verbatim, adjust names**

Copy `phpunit.xml.dist`, `pint.json`, `rector.php` from `D:/Code/Projects/KurtModules-Core`. Write `phpstan.neon`:

```neon
includes:
    - vendor/larastan/larastan/extension.neon
parameters:
    level: 8
    paths:
        - src
    tmpDir: build/phpstan
```

- [ ] **Step 3: Write the provider skeleton**

```php
<?php

declare(strict_types=1);

namespace Kurt\Modules\Loyalty\Providers;

use Kurt\Modules\Core\Providers\PackageServiceProvider;
use Spatie\LaravelPackageTools\Package;

final class LoyaltyServiceProvider extends PackageServiceProvider
{
    protected function module(): string
    {
        return 'loyalty';
    }

    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-modules-loyalty')
            ->hasConfigFile()
            ->hasTranslations()
            ->hasMigrations([
                'create_loyalty_programs_table',
                'create_loyalty_cards_table',
                'create_loyalty_stamps_table',
                'create_loyalty_vouchers_table',
                'create_loyalty_redemptions_table',
                'create_loyalty_wallet_passes_table',
                'create_loyalty_wallet_registrations_table',
            ]);
    }
}
```

- [ ] **Step 4: Write `config/loyalty.php`** (M1 keys only; later milestones extend it)

```php
<?php

use Kurt\Modules\Loyalty\Enums\IdentityMode;

return [
    // How cards are owned: IdentityMode::Anonymous | User | AnonymousClaimable
    'identity_mode' => IdentityMode::AnonymousClaimable->value,

    // Anti-fraud guards applied by StampService.
    'throttle' => [
        'cooldown_seconds' => 30,   // min seconds between stamps on one card
        'max_per_day' => null,      // null = unlimited stamps/day per card
    ],

    // Reward behaviour when a card completes.
    'reset_on_reward' => true,      // true = reset to 0; false = roll surplus over
];
```

- [ ] **Step 5: Write `tests/TestCase.php` + `tests/Pest.php`**

```php
<?php
// tests/TestCase.php
declare(strict_types=1);

namespace Kurt\Modules\Loyalty\Tests;

use Kurt\Modules\Core\Testing\PackageTestCase;
use Kurt\Modules\Loyalty\Providers\LoyaltyServiceProvider;

abstract class TestCase extends PackageTestCase
{
    protected function modulePackageProviders($app): array
    {
        return [LoyaltyServiceProvider::class];
    }

    protected function defineDatabaseMigrations(): void
    {
        parent::defineDatabaseMigrations();               // creates users table
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
```

```php
<?php
// tests/Pest.php
uses(Kurt\Modules\Loyalty\Tests\TestCase::class)->in('Feature', 'Unit');
```

- [ ] **Step 6: Write the sanity test**

```php
<?php
// tests/Unit/SanityTest.php
it('boots the package', function () {
    expect(config('loyalty.identity_mode'))->toBe('anonymous_claimable');
});
```

- [ ] **Step 7: Install deps**

Run: `"C:/laragon/bin/php/php-8.4.5-nts-Win32-vs17-x64/php.exe" C:/laragon/bin/composer/composer.phar update --prefer-stable --prefer-dist --no-interaction`
Expected: resolves `ozankurt/laravel-modules-core` from GitHub VCS (tag `v2.0.0`), writes `vendor/`.

- [ ] **Step 8: Run the suite**

Run: `"C:/laragon/bin/php/php-8.4.5-nts-Win32-vs17-x64/php.exe" vendor/bin/pest`
Expected: PASS (1 test). The config assertion proves the provider + config file load.

- [ ] **Step 9: Commit**

```bash
git add -A && git commit -m "feat: scaffold loyalty package with green test harness"
```

---

### Task 2: Enums

**Files:**
- Create: `src/Enums/IdentityMode.php`, `CardStatus.php`, `StampSource.php`, `WalletPlatform.php`
- Test: `tests/Unit/EnumsTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Unit/EnumsTest.php
use Kurt\Modules\Loyalty\Enums\{IdentityMode, CardStatus, StampSource, WalletPlatform};

it('exposes identity modes', function () {
    expect(IdentityMode::from('anonymous_claimable'))->toBe(IdentityMode::AnonymousClaimable);
    expect(IdentityMode::cases())->toHaveCount(3);
});

it('exposes stamp sources and card statuses', function () {
    expect(StampSource::from('staff_terminal'))->toBe(StampSource::StaffTerminal);
    expect(CardStatus::from('active'))->toBe(CardStatus::Active);
    expect(WalletPlatform::cases())->toHaveCount(2);
});
```

- [ ] **Step 2: Run test to verify it fails** — Run: `… vendor/bin/pest --filter=Enums` → FAIL (classes missing).

- [ ] **Step 3: Write the enums** (all string-backed)

```php
<?php
declare(strict_types=1);
namespace Kurt\Modules\Loyalty\Enums;

enum IdentityMode: string {
    case Anonymous = 'anonymous';
    case User = 'user';
    case AnonymousClaimable = 'anonymous_claimable';
}
```
```php
<?php
declare(strict_types=1);
namespace Kurt\Modules\Loyalty\Enums;
enum CardStatus: string { case Active = 'active'; case Completed = 'completed'; case Disabled = 'disabled'; }
```
```php
<?php
declare(strict_types=1);
namespace Kurt\Modules\Loyalty\Enums;
enum StampSource: string {
    case StaffTerminal = 'staff_terminal';
    case Receipt = 'receipt';
    case TillQr = 'till_qr';
    case Api = 'api';
    case Manual = 'manual';
}
```
```php
<?php
declare(strict_types=1);
namespace Kurt\Modules\Loyalty\Enums;
enum WalletPlatform: string { case Apple = 'apple'; case Google = 'google'; }
```

- [ ] **Step 4: Run test** — Expected: PASS.
- [ ] **Step 5: Commit** — `git commit -am "feat: add loyalty enums"`

---

### Task 3: Migrations

**Files:** Create 7 anonymous migrations in `database/migrations/` (timestamp-prefixed, ordered as listed in the provider).

- [ ] **Step 1: Write `create_loyalty_programs_table`**

```php
<?php
declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('loyalty_programs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->nullableMorphs('owner');
            $table->json('name');                 // translatable
            $table->string('slug')->unique();
            $table->json('reward');               // translatable
            $table->unsignedSmallInteger('stamps_required')->default(10);
            $table->string('theme')->default('coffee');
            $table->string('icon')->default('coffee');
            $table->unsignedInteger('cooldown_seconds')->default(30);
            $table->unsignedInteger('max_per_day')->nullable();
            $table->boolean('reset_on_reward')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void { Schema::dropIfExists('loyalty_programs'); }
};
```

- [ ] **Step 2: Write `create_loyalty_cards_table`**

```php
// up():
Schema::create('loyalty_cards', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->foreignId('program_id')->constrained('loyalty_programs')->cascadeOnDelete();
    $table->string('token', 32)->unique();
    $table->unsignedBigInteger('user_id')->nullable()->index();
    $table->string('email')->nullable()->index();
    $table->string('phone')->nullable()->index();
    $table->unsignedInteger('stamps_count')->default(0);
    $table->unsignedInteger('rewards_earned')->default(0);
    $table->unsignedInteger('rewards_redeemed')->default(0);
    $table->string('status')->default('active');
    $table->timestamp('last_stamped_at')->nullable();
    $table->timestamps();
    $table->softDeletes();
});
```

- [ ] **Step 3: Write `create_loyalty_stamps_table`** (immutable, no soft delete)

```php
Schema::create('loyalty_stamps', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->foreignId('card_id')->constrained('loyalty_cards')->cascadeOnDelete();
    $table->foreignId('voucher_id')->nullable()->constrained('loyalty_vouchers')->nullOnDelete();
    $table->string('source')->default('manual');
    $table->string('granted_by')->nullable();
    $table->timestamp('created_at')->nullable();
});
```
> Note: `voucher_id` FK references `loyalty_vouchers`; ensure the vouchers migration timestamp precedes this one. Reorder filenames so vouchers migrates before stamps.

- [ ] **Step 4: Write `create_loyalty_vouchers_table`** (must sort BEFORE stamps)

```php
Schema::create('loyalty_vouchers', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->foreignId('program_id')->constrained('loyalty_programs')->cascadeOnDelete();
    $table->string('token', 40)->unique();
    $table->unsignedSmallInteger('stamps')->default(1);
    $table->string('issued_by')->nullable();
    $table->timestamp('expires_at')->nullable();
    $table->timestamp('redeemed_at')->nullable();
    $table->foreignId('redeemed_by_card_id')->nullable()->constrained('loyalty_cards')->nullOnDelete();
    $table->string('status')->default('pending');   // pending | redeemed | expired
    $table->timestamps();
});
```
> Fix ordering: rename files so the sequence is programs → cards → vouchers → stamps → redemptions → wallet_passes → wallet_registrations, and update the `hasMigrations([...])` array in the provider to match.

- [ ] **Step 5: Write `create_loyalty_redemptions_table`**

```php
Schema::create('loyalty_redemptions', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->foreignId('card_id')->constrained('loyalty_cards')->cascadeOnDelete();
    $table->json('reward');               // snapshot at redemption time
    $table->string('redeemed_by')->nullable();
    $table->timestamp('created_at')->nullable();
});
```

- [ ] **Step 6: Write `create_loyalty_wallet_passes_table` + `create_loyalty_wallet_registrations_table`**

```php
Schema::create('loyalty_wallet_passes', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->foreignId('card_id')->constrained('loyalty_cards')->cascadeOnDelete();
    $table->string('platform');           // apple | google
    $table->string('external_id')->nullable();
    $table->string('auth_token')->nullable();
    $table->timestamp('last_pushed_at')->nullable();
    $table->timestamps();
    $table->unique(['card_id', 'platform']);
});
Schema::create('loyalty_wallet_registrations', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->string('device_library_id');
    $table->string('push_token');
    $table->string('pass_serial')->index();
    $table->timestamps();
    $table->unique(['device_library_id', 'pass_serial']);
});
```

- [ ] **Step 7: Migration smoke test**

```php
<?php
// tests/Feature/MigrationsTest.php
use Illuminate\Support\Facades\Schema;
it('creates all loyalty tables', function () {
    foreach (['loyalty_programs','loyalty_cards','loyalty_stamps','loyalty_vouchers',
              'loyalty_redemptions','loyalty_wallet_passes','loyalty_wallet_registrations'] as $t) {
        expect(Schema::hasTable($t))->toBeTrue();
    }
});
```
Run: `… vendor/bin/pest --filter=Migrations` → Expected: PASS.

- [ ] **Step 8: Commit** — `git commit -am "feat: add loyalty schema migrations"`

---

### Task 4: Models + factories

**Files:** Create `src/Models/{Program,Card,Stamp,Voucher,Redemption,WalletPass,WalletRegistration}.php` and matching factories under `database/factories/Kurt/Modules/Loyalty/`. Create `src/Contracts/LoyaltyCustomer.php` + `src/Concerns/IsLoyaltyCustomer.php`.

- [ ] **Step 1: Write failing model test**

```php
<?php
// tests/Feature/ModelsTest.php
use Kurt\Modules\Loyalty\Models\{Program, Card, Voucher, Stamp};

it('creates a program with a card and relationships resolve', function () {
    $program = Program::factory()->create(['stamps_required' => 7]);
    $card = Card::factory()->for($program)->create();

    expect($card->program->is($program))->toBeTrue();
    expect($program->cards()->count())->toBe(1);
    expect($card->stamps_count)->toBe(0);
});

it('casts translatable name and reward', function () {
    $program = Program::factory()->create();
    $program->setTranslation('name', 'en', 'Coffee Club')->save();
    expect($program->getTranslation('name', 'en'))->toBe('Coffee Club');
});
```

- [ ] **Step 2: Run → FAIL** (models missing).

- [ ] **Step 3: Write models.** `Program` (HasFactory, SoftDeletes, HasTranslations; `$translatable = ['name','reward']`; casts `reward`/`name` handled by translatable; `stamps_required` int, `reset_on_reward` bool; `cards()` hasMany; `vouchers()` hasMany; `owner()` morphTo). `Card` (HasFactory, SoftDeletes; `program()` belongsTo, `stamps()` hasMany, `redemptions()` hasMany, `walletPasses()` hasMany; casts `status` => CardStatus, `last_stamped_at` => datetime; counters int). `Stamp` (HasFactory, no SoftDeletes, `$timestamps = false` except created_at — set `const UPDATED_AT = null`; casts `source` => StampSource; `card()`, `voucher()` belongsTo). `Voucher` (HasFactory; casts `status` string, `expires_at`/`redeemed_at` datetime; `program()`, `redeemedByCard()` belongsTo; helper `isRedeemable(): bool`). `Redemption` (`const UPDATED_AT = null`; `card()` belongsTo; `reward` array cast). `WalletPass`, `WalletRegistration` (plain). Example `Card`:

```php
<?php
declare(strict_types=1);
namespace Kurt\Modules\Loyalty\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kurt\Modules\Loyalty\Enums\CardStatus;

class Card extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'loyalty_cards';
    protected $guarded = [];
    protected $casts = [
        'status' => CardStatus::class,
        'last_stamped_at' => 'datetime',
        'stamps_count' => 'integer',
        'rewards_earned' => 'integer',
        'rewards_redeemed' => 'integer',
    ];

    public function program() { return $this->belongsTo(Program::class); }
    public function stamps()   { return $this->hasMany(Stamp::class); }
    public function redemptions() { return $this->hasMany(Redemption::class); }
    public function walletPasses() { return $this->hasMany(WalletPass::class); }

    public function isComplete(): bool
    {
        return $this->stamps_count >= $this->program->stamps_required;
    }
}
```

- [ ] **Step 4: Write factories.** `ProgramFactory` (name/reward as `['en' => ...]` arrays, `slug` unique, `stamps_required` 10). `CardFactory` (`token` = `Str::random(12)` lowercased-hex via `bin2hex(random_bytes(6))`, `program_id` via Program factory). Voucher/Stamp/etc. Example:

```php
<?php
declare(strict_types=1);
namespace Database\Factories\Kurt\Modules\Loyalty;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Kurt\Modules\Loyalty\Models\Card;
use Kurt\Modules\Loyalty\Models\Program;

class CardFactory extends Factory
{
    protected $model = Card::class;
    public function definition(): array
    {
        return [
            'program_id' => Program::factory(),
            'token' => bin2hex(random_bytes(6)),
        ];
    }
}
```
> Each model needs `newFactory()` or the `HasFactory` resolver to find these namespaced factories. Add to each model:
```php
use Illuminate\Database\Eloquent\Factories\Factory;
protected static function newFactory(): Factory
{
    return \Database\Factories\Kurt\Modules\Loyalty\CardFactory::new();
}
```
(One per model, pointing at its own factory.)

- [ ] **Step 5: Write the `LoyaltyCustomer` contract + `IsLoyaltyCustomer` trait**

```php
<?php
declare(strict_types=1);
namespace Kurt\Modules\Loyalty\Contracts;
interface LoyaltyCustomer {}
```
```php
<?php
declare(strict_types=1);
namespace Kurt\Modules\Loyalty\Concerns;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Kurt\Modules\Loyalty\Models\Card;
trait IsLoyaltyCustomer
{
    public function loyaltyCards(): HasMany
    {
        return $this->hasMany(Card::class, 'user_id');
    }
}
```

- [ ] **Step 6: Run → PASS.**
- [ ] **Step 7: Commit** — `git commit -am "feat: add loyalty models, factories, customer trait"`

---

### Task 5: CardService (creation + claim)

**Files:** Create `src/Services/CardService.php`, `src/Events/CardCreated.php`, `CardClaimed.php`, `src/Exceptions/CardNotClaimableException.php`. Test: `tests/Feature/CardServiceTest.php`.

- [ ] **Step 1: Write the failing test**

```php
<?php
use Illuminate\Support\Facades\Event;
use Kurt\Modules\Loyalty\Enums\IdentityMode;
use Kurt\Modules\Loyalty\Events\{CardCreated, CardClaimed};
use Kurt\Modules\Loyalty\Exceptions\CardNotClaimableException;
use Kurt\Modules\Loyalty\Models\Program;
use Kurt\Modules\Loyalty\Services\CardService;

beforeEach(fn () => $this->program = Program::factory()->create());

it('creates an anonymous card with a unique token and fires an event', function () {
    Event::fake();
    $card = app(CardService::class)->create($this->program);
    expect($card->token)->not->toBeEmpty()
        ->and($card->user_id)->toBeNull()
        ->and($card->stamps_count)->toBe(0);
    Event::assertDispatched(CardCreated::class);
});

it('claims an anonymous card by email when mode allows', function () {
    config()->set('loyalty.identity_mode', IdentityMode::AnonymousClaimable->value);
    Event::fake();
    $card = app(CardService::class)->create($this->program);
    $card = app(CardService::class)->claim($card, ['email' => 'a@b.com']);
    expect($card->email)->toBe('a@b.com');
    Event::assertDispatched(CardClaimed::class);
});

it('refuses to claim when identity mode is anonymous', function () {
    config()->set('loyalty.identity_mode', IdentityMode::Anonymous->value);
    $card = app(CardService::class)->create($this->program);
    app(CardService::class)->claim($card, ['email' => 'a@b.com']);
})->throws(CardNotClaimableException::class);
```

- [ ] **Step 2: Run → FAIL.**

- [ ] **Step 3: Implement `CardService`**

```php
<?php
declare(strict_types=1);
namespace Kurt\Modules\Loyalty\Services;

use Kurt\Modules\Loyalty\Enums\IdentityMode;
use Kurt\Modules\Loyalty\Events\CardClaimed;
use Kurt\Modules\Loyalty\Events\CardCreated;
use Kurt\Modules\Loyalty\Exceptions\CardNotClaimableException;
use Kurt\Modules\Loyalty\Models\Card;
use Kurt\Modules\Loyalty\Models\Program;

final class CardService
{
    public function create(Program $program, array $attributes = []): Card
    {
        $card = $program->cards()->create([
            'token' => $this->uniqueToken(),
            'user_id' => $attributes['user_id'] ?? null,
            'email' => $attributes['email'] ?? null,
            'phone' => $attributes['phone'] ?? null,
        ]);
        event(new CardCreated($card));
        return $card;
    }

    public function claim(Card $card, array $identity): Card
    {
        if ($this->mode() === IdentityMode::Anonymous) {
            throw new CardNotClaimableException('Cards are anonymous in this install.');
        }
        $card->fill(array_intersect_key($identity, array_flip(['user_id', 'email', 'phone'])))->save();
        event(new CardClaimed($card));
        return $card->refresh();
    }

    private function mode(): IdentityMode
    {
        return IdentityMode::from(config('loyalty.identity_mode'));
    }

    private function uniqueToken(): string
    {
        do { $token = bin2hex(random_bytes(6)); }
        while (Card::where('token', $token)->exists());
        return $token;
    }
}
```
Events are simple readonly carriers:
```php
<?php
declare(strict_types=1);
namespace Kurt\Modules\Loyalty\Events;
use Kurt\Modules\Loyalty\Models\Card;
final class CardCreated { public function __construct(public readonly Card $card) {} }
```
(Repeat for `CardClaimed` with the same shape.) Exception:
```php
<?php
declare(strict_types=1);
namespace Kurt\Modules\Loyalty\Exceptions;
final class CardNotClaimableException extends \RuntimeException {}
```

- [ ] **Step 4: Run → PASS.**
- [ ] **Step 5: Commit** — `git commit -am "feat: add CardService with create/claim + events"`

---

### Task 6: VoucherService (issue + redeem, single-use/expiry)

**Files:** Create `src/Services/VoucherService.php`, `src/Events/VoucherIssued.php`, `VoucherRedeemed.php`, `src/Exceptions/VoucherAlreadyRedeemedException.php`, `VoucherExpiredException.php`. Test: `tests/Feature/VoucherServiceTest.php`. **Depends on StampService (Task 7) for redeem →** implement `issue()` here fully; `redeem()` delegates the actual stamping to `StampService`, so write `redeem()` to call `app(StampService::class)->applyVoucher($card, $voucher)` and test the guard/expiry logic with a stubbed count assertion after Task 7. To keep TDD honest, split: this task implements + tests `issue()` and the redeemable-state guards; the stamping assertion lands in Task 7.

- [ ] **Step 1: Failing test for issue + guards**

```php
<?php
use Kurt\Modules\Loyalty\Models\{Program, Voucher};
use Kurt\Modules\Loyalty\Services\VoucherService;
use Kurt\Modules\Loyalty\Enums\StampSource;

it('issues a pending single-use voucher', function () {
    $program = Program::factory()->create();
    $voucher = app(VoucherService::class)->issue($program, stamps: 1, source: StampSource::StaffTerminal);
    expect($voucher->status)->toBe('pending')
        ->and($voucher->stamps)->toBe(1)
        ->and($voucher->token)->not->toBeEmpty()
        ->and($voucher->isRedeemable())->toBeTrue();
});

it('marks an expired voucher not redeemable', function () {
    $program = Program::factory()->create();
    $voucher = app(VoucherService::class)->issue($program, expiresInSeconds: -10);
    expect($voucher->isRedeemable())->toBeFalse();
});
```

- [ ] **Step 2: Run → FAIL.**

- [ ] **Step 3: Implement `Voucher::isRedeemable()` + `VoucherService::issue()`**

```php
// Voucher.php
public function isRedeemable(): bool
{
    if ($this->status !== 'pending') return false;
    if ($this->expires_at !== null && $this->expires_at->isPast()) return false;
    return true;
}
```
```php
<?php
declare(strict_types=1);
namespace Kurt\Modules\Loyalty\Services;

use Kurt\Modules\Loyalty\Enums\StampSource;
use Kurt\Modules\Loyalty\Events\VoucherIssued;
use Kurt\Modules\Loyalty\Models\Program;
use Kurt\Modules\Loyalty\Models\Voucher;

final class VoucherService
{
    public function issue(
        Program $program,
        int $stamps = 1,
        ?StampSource $source = null,
        ?int $expiresInSeconds = null,
        ?string $issuedBy = null,
    ): Voucher {
        $voucher = $program->vouchers()->create([
            'token' => bin2hex(random_bytes(20)),
            'stamps' => $stamps,
            'issued_by' => $issuedBy,
            'expires_at' => $expiresInSeconds !== null ? now()->addSeconds($expiresInSeconds) : null,
            'status' => 'pending',
        ]);
        event(new VoucherIssued($voucher, $source));
        return $voucher;
    }
}
```
Add `vouchers()` hasMany to `Program`. `VoucherIssued` carries `Voucher $voucher` + `?StampSource $source`.

- [ ] **Step 4: Run → PASS.**
- [ ] **Step 5: Commit** — `git commit -am "feat: add VoucherService issue + redeemable guard"`

---

### Task 7: StampService (guarded write) + voucher redeem wiring

**Files:** Create `src/Services/StampService.php`, `src/Events/StampAdded.php`, `CardCompleted.php`, `src/Exceptions/StampThrottledException.php`, `DailyStampLimitReachedException.php`. Modify `VoucherService` to add `redeem()`. Test: `tests/Feature/StampServiceTest.php`, extend `VoucherServiceTest`.

- [ ] **Step 1: Failing tests**

```php
<?php
use Illuminate\Support\Facades\Event;
use Kurt\Modules\Loyalty\Enums\StampSource;
use Kurt\Modules\Loyalty\Events\{StampAdded, CardCompleted};
use Kurt\Modules\Loyalty\Exceptions\StampThrottledException;
use Kurt\Modules\Loyalty\Models\{Program, Card};
use Kurt\Modules\Loyalty\Services\StampService;

it('adds a stamp, increments the counter and logs immutably', function () {
    Event::fake();
    $program = Program::factory()->create(['stamps_required' => 3, 'cooldown_seconds' => 0]);
    $card = Card::factory()->for($program)->create();

    app(StampService::class)->add($card, source: StampSource::Manual);

    expect($card->refresh()->stamps_count)->toBe(1)
        ->and($card->stamps()->count())->toBe(1)
        ->and($card->last_stamped_at)->not->toBeNull();
    Event::assertDispatched(StampAdded::class);
});

it('rejects a second stamp inside the cooldown window', function () {
    $program = Program::factory()->create(['cooldown_seconds' => 60]);
    $card = Card::factory()->for($program)->create();
    app(StampService::class)->add($card, source: StampSource::Manual);
    app(StampService::class)->add($card, source: StampSource::Manual);
})->throws(StampThrottledException::class);

it('fires CardCompleted and marks rewards_earned when reaching the goal', function () {
    Event::fake();
    $program = Program::factory()->create(['stamps_required' => 2, 'cooldown_seconds' => 0]);
    $card = Card::factory()->for($program)->create();
    app(StampService::class)->add($card, source: StampSource::Manual);
    app(StampService::class)->add($card, source: StampSource::Manual);
    expect($card->refresh()->rewards_earned)->toBe(1);
    Event::assertDispatched(CardCompleted::class);
});

it('redeems a voucher exactly once and adds its stamps', function () {
    $program = Program::factory()->create(['cooldown_seconds' => 0]);
    $card = Card::factory()->for($program)->create();
    $voucher = app(\Kurt\Modules\Loyalty\Services\VoucherService::class)->issue($program, stamps: 1);

    app(\Kurt\Modules\Loyalty\Services\VoucherService::class)->redeem($voucher, $card);
    expect($card->refresh()->stamps_count)->toBe(1)
        ->and($voucher->refresh()->status)->toBe('redeemed');

    // second redeem is a no-op guard
    expect(fn () => app(\Kurt\Modules\Loyalty\Services\VoucherService::class)->redeem($voucher, $card))
        ->toThrow(\Kurt\Modules\Loyalty\Exceptions\VoucherAlreadyRedeemedException::class);
});
```

- [ ] **Step 2: Run → FAIL.**

- [ ] **Step 3: Implement `StampService`**

```php
<?php
declare(strict_types=1);
namespace Kurt\Modules\Loyalty\Services;

use Illuminate\Support\Facades\DB;
use Kurt\Modules\Loyalty\Enums\StampSource;
use Kurt\Modules\Loyalty\Events\CardCompleted;
use Kurt\Modules\Loyalty\Events\StampAdded;
use Kurt\Modules\Loyalty\Exceptions\DailyStampLimitReachedException;
use Kurt\Modules\Loyalty\Exceptions\StampThrottledException;
use Kurt\Modules\Loyalty\Models\Card;
use Kurt\Modules\Loyalty\Models\Voucher;

final class StampService
{
    public function add(Card $card, StampSource $source, ?Voucher $voucher = null, ?string $grantedBy = null): Card
    {
        return DB::transaction(function () use ($card, $source, $voucher, $grantedBy) {
            $card = Card::whereKey($card->getKey())->lockForUpdate()->firstOrFail();
            $this->guardThrottle($card);
            $this->guardDailyLimit($card);

            $card->stamps()->create([
                'voucher_id' => $voucher?->getKey(),
                'source' => $source->value,
                'granted_by' => $grantedBy,
                'created_at' => now(),
            ]);
            $card->forceFill([
                'stamps_count' => $card->stamps_count + 1,
                'last_stamped_at' => now(),
            ])->save();

            event(new StampAdded($card, $source));
            $this->maybeComplete($card);
            return $card->refresh();
        });
    }

    private function guardThrottle(Card $card): void
    {
        $cooldown = (int) $card->program->cooldown_seconds;
        if ($cooldown > 0 && $card->last_stamped_at !== null
            && $card->last_stamped_at->diffInSeconds(now()) < $cooldown) {
            throw new StampThrottledException("Stamp rejected: cooldown of {$cooldown}s not elapsed.");
        }
    }

    private function guardDailyLimit(Card $card): void
    {
        $max = $card->program->max_per_day;
        if ($max === null) return;
        $today = $card->stamps()->whereDate('created_at', now()->toDateString())->count();
        if ($today >= $max) {
            throw new DailyStampLimitReachedException("Daily stamp limit of {$max} reached.");
        }
    }

    private function maybeComplete(Card $card): void
    {
        if ($card->stamps_count >= $card->program->stamps_required) {
            $card->forceFill(['rewards_earned' => $card->rewards_earned + 1])->save();
            event(new CardCompleted($card));
        }
    }
}
```
> Config-level `throttle.cooldown_seconds` / `max_per_day` are defaults; the per-program columns win. In M1 the program columns are authoritative (factory default cooldown 30). Tests set them explicitly.

- [ ] **Step 4: Add `VoucherService::redeem()`**

```php
public function redeem(Voucher $voucher, Card $card): Card
{
    return DB::transaction(function () use ($voucher, $card) {
        $voucher = Voucher::whereKey($voucher->getKey())->lockForUpdate()->firstOrFail();
        if ($voucher->status === 'redeemed') {
            throw new VoucherAlreadyRedeemedException('Voucher already redeemed.');
        }
        if (! $voucher->isRedeemable()) {
            throw new VoucherExpiredException('Voucher is expired or not redeemable.');
        }
        $card = app(StampService::class)->add($card, StampSource::Api, $voucher);
        // add remaining stamps if voucher grants > 1 (respect throttle=0 within same txn is fine)
        for ($i = 1; $i < $voucher->stamps; $i++) {
            $card = app(StampService::class)->add($card, StampSource::Api, $voucher);
        }
        $voucher->forceFill([
            'status' => 'redeemed',
            'redeemed_at' => now(),
            'redeemed_by_card_id' => $card->getKey(),
        ])->save();
        event(new VoucherRedeemed($voucher, $card));
        return $card;
    });
}
```
> Multi-stamp vouchers with a non-zero cooldown would trip the throttle. For M1, `redeem()` bypasses cooldown for the *same* voucher by passing a flag: add a `bool $bypassThrottle = false` param to `StampService::add()` and set it true from `redeem()`. Update the throttle guard to early-return when `$bypassThrottle`. Add `VoucherRedeemed(Voucher, Card)`, and the two exceptions (extend `\RuntimeException`).

- [ ] **Step 5: Run → PASS** (all StampService + Voucher redeem tests).
- [ ] **Step 6: Commit** — `git commit -am "feat: add StampService guards + voucher redemption"`

---

### Task 8: RedemptionService (claim reward, reset/rollover)

**Files:** Create `src/Services/RedemptionService.php`, `src/Events/RewardRedeemed.php`. Test: `tests/Feature/RedemptionServiceTest.php`.

- [ ] **Step 1: Failing test**

```php
<?php
use Illuminate\Support\Facades\Event;
use Kurt\Modules\Loyalty\Events\RewardRedeemed;
use Kurt\Modules\Loyalty\Models\{Program, Card};
use Kurt\Modules\Loyalty\Services\RedemptionService;

it('redeems a completed reward and resets stamps when reset_on_reward', function () {
    Event::fake();
    $program = Program::factory()->create(['stamps_required' => 3, 'reset_on_reward' => true]);
    $card = Card::factory()->for($program)->create(['stamps_count' => 3, 'rewards_earned' => 1]);

    app(RedemptionService::class)->redeem($card, redeemedBy: 'till-1');

    $card->refresh();
    expect($card->stamps_count)->toBe(0)
        ->and($card->rewards_redeemed)->toBe(1)
        ->and($card->redemptions()->count())->toBe(1);
    Event::assertDispatched(RewardRedeemed::class);
});

it('rolls the surplus over when reset_on_reward is false', function () {
    $program = Program::factory()->create(['stamps_required' => 3, 'reset_on_reward' => false]);
    $card = Card::factory()->for($program)->create(['stamps_count' => 4, 'rewards_earned' => 1]);
    app(RedemptionService::class)->redeem($card, redeemedBy: null);
    expect($card->refresh()->stamps_count)->toBe(1);   // 4 - 3
});

it('refuses to redeem when the card has no earned reward', function () {
    $program = Program::factory()->create(['stamps_required' => 3]);
    $card = Card::factory()->for($program)->create(['stamps_count' => 1, 'rewards_earned' => 0]);
    app(RedemptionService::class)->redeem($card);
})->throws(\RuntimeException::class);
```

- [ ] **Step 2: Run → FAIL.**

- [ ] **Step 3: Implement `RedemptionService`**

```php
<?php
declare(strict_types=1);
namespace Kurt\Modules\Loyalty\Services;

use Illuminate\Support\Facades\DB;
use Kurt\Modules\Loyalty\Events\RewardRedeemed;
use Kurt\Modules\Loyalty\Models\Card;

final class RedemptionService
{
    public function redeem(Card $card, ?string $redeemedBy = null): Card
    {
        return DB::transaction(function () use ($card, $redeemedBy) {
            $card = Card::whereKey($card->getKey())->lockForUpdate()->firstOrFail();
            $available = $card->rewards_earned - $card->rewards_redeemed;
            if ($available < 1) {
                throw new \RuntimeException('No reward available to redeem.');
            }

            $card->redemptions()->create([
                'reward' => $card->program->getTranslations('reward'),
                'redeemed_by' => $redeemedBy,
                'created_at' => now(),
            ]);

            $required = (int) $card->program->stamps_required;
            $newCount = $card->program->reset_on_reward
                ? 0
                : max(0, $card->stamps_count - $required);

            $card->forceFill([
                'rewards_redeemed' => $card->rewards_redeemed + 1,
                'stamps_count' => $newCount,
            ])->save();

            event(new RewardRedeemed($card));
            return $card->refresh();
        });
    }
}
```
`RewardRedeemed` carries `Card $card`.

- [ ] **Step 4: Run → PASS.**
- [ ] **Step 5: Commit** — `git commit -am "feat: add RedemptionService with reset/rollover"`

---

### Task 9: Milestone gate — lint, stan, full suite, docs, push

- [ ] **Step 1: Full suite** — Run: `… vendor/bin/pest` → Expected: all green.
- [ ] **Step 2: Pint** — Run: `… vendor/bin/pint` → format, then `… vendor/bin/pint --test` → PASS.
- [ ] **Step 3: PHPStan** — Run: `… vendor/bin/phpstan analyse --memory-limit=2G` → resolve level-8 findings (add generics/return types on relations as needed).
- [ ] **Step 4: Write README** — module intro, the convention-departure note (public surface + no Filament, per spec §1), install (`composer require`, `vendor:publish` tags to come in later milestones), M1 status. Add `CHANGELOG.md` (`## Unreleased`), `LICENSE.md` (MIT, Ozan Kurt), `.gitignore` (mirror Core: `/vendor /build .phpunit.result.cache .idea`).
- [ ] **Step 5: CI** — copy `.github/workflows/tests.yml` from Core; adjust matrix to drop Filament (this module has none): `php: [8.3, 8.4]` × `laravel: [12.*, 13.*]`; steps pint → stan → pest. Keep coverage note (gate runs in CI only).
- [ ] **Step 6: Commit + push** — `git commit -am "docs: M1 readme, changelog, CI" && git push -u origin main`.

---

## Self-Review

**Spec coverage (M1 slice):** identity modes (Task 5 ✓), programs/cards/stamps/vouchers/redemptions/wallet tables (Task 3 ✓), unified voucher primitive + single-use/expiry/idempotency (Tasks 6–7 ✓), StampService cooldown + daily cap (Task 7 ✓), reward completion + reset/rollover (Task 8 ✓), events with no default listeners (Tasks 5–8 ✓), UserResolver/customer trait (Task 4 ✓), Core-based provider + test base (Tasks 1,4 ✓). Deferred by design to later milestones: HTTP surface (M2), Blade+JS+Vite+themes (M3), Apple/Google wallet providers + web service + push (M4). Wallet *tables* land here so M4 has no schema churn.

**Placeholder scan:** none — every step has concrete code or an exact command.

**Type consistency:** `StampService::add(Card, StampSource, ?Voucher, ?string, bool $bypassThrottle=false)` used consistently by `VoucherService::redeem()`; `isRedeemable()` defined in Task 6 and used in Task 7; counter fields (`stamps_count`, `rewards_earned`, `rewards_redeemed`) named identically across migration, model, and services; event constructors (`CardCreated(Card)`, `StampAdded(Card, StampSource)`, `VoucherIssued(Voucher, ?StampSource)`, `VoucherRedeemed(Voucher, Card)`, `RewardRedeemed(Card)`) consistent.

**Note:** Migration filename ordering must place `vouchers` before `stamps` (FK dependency) — called out in Tasks 3.3/3.4 and reflected in the provider's `hasMigrations([...])` order.

---

## Later milestones (separate plans, after M1 is green)
- **M2 — HTTP surface:** customer routes/controllers, `/state` JSON, `/v/{token}` voucher redeem, staff `/terminal` behind `loyalty:staff` Gate, rate limiting, signed tokens.
- **M3 — Frontend:** Blade views (data-attribute contract), vanilla JS behavior layer, Vite build + prebuilt dist, contract stylesheet + `coffee/hotdog/restaurant/minimal` themes (light+dark) + icon sprite, optional broadcast driver.
- **M4 — Wallet:** `WalletProvider` seam, Apple `.pkpass` + APNs web service, Google Wallet JWT + object patch, `loyalty:install/demo/prune-vouchers/wallet-check` commands.
