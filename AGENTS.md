# AGENTS.md — RAHA-TELE — Laravel API Backend + Filament Admin

You are an expert Laravel 12 + PHP 8.2 engineer helping build and maintain the
**RAHA-TELE** backend — a credit-driven service marketplace connecting clients
with escorts. This Laravel application is the single source of truth for the
whole platform and serves two consumers:

1. **Next.js frontend** — the member- and escort-facing site. It is a separate
   application that consumes this backend's JSON API. Members and escorts never
   touch Filament; everything they do goes through the API.
2. **Filament 4 admin panel (+ Filament Shield)** — the internal admin portal
   at `/admin`, for platform staff only.

You write clean, simple, maintainable code. You follow Laravel conventions
strictly and never overengineer solutions.

> **Governing document:** `_docs/RAHA-TELE_Statement_of_Work.pdf` (SOW v1.0,
> prepared by Aggrey Mutagaywa for Hencan Technologies). The SOW defines the
> full scope of new features, phases, and the monetization model. Read it
> before starting any feature listed under "Scope of Work".

---

## Project Overview

RAHA-TELE is a **credit-based service marketplace**. The platform's entire
economy runs on credits:

- **Clients (members)** buy credit bundles (e.g. 100 credits for KES 500 via
  M-Pesa) and spend them to interact with escorts — unlocking phone numbers,
  sending paid messages, starting paid conversations.
- **Escorts** publish profiles, receive credits when clients spend on them
  (minus a platform commission), and withdraw earnings via M-Pesa.
- **The platform** earns revenue from credit sales, a commission on every
  escort transaction (default 30% platform / 70% escort), and featured/boosted
  profile listings.

The system has three actor types (stored in `users.user_type`), distinguished
by a denormalized enum column for fast queries — `system_user`, `escort`,
`member`. Fine-grained authorisation is handled by **Spatie Laravel
Permission** roles + Filament Shield (see "Auth & Security Rules").

- **system_user** — platform staff (super_admin, admin, manager, moderator,
  support, etc.). Multiple Spatie sub-roles scope their permissions.
  Works exclusively in the **Filament** panel.
- **escort** — service provider; owns an `Escort` profile, gallery, rates,
  earnings, and withdrawals. Works exclusively in the **Next.js** frontend via
  the API.
- **member** — client; buys credits and interacts with escorts. Works
  exclusively in the **Next.js** frontend via the API.

---

## Tech Stack

- PHP 8.2, Laravel 12 (framework)
- **Filament 4 + Filament Shield** — admin panel and its role/permission UI
  (`/admin`, admin-only). See "Admin Portal" below.
- **Laravel Sanctum** — token auth for the Next.js frontend API
- Spatie Laravel Permission (RBAC backing Filament Shield: admin / escort /
  member)
- MySQL (primary database)
- Broadcasting: **Pusher** (primary, configured) + Laravel Reverb (available)
  for real-time chat consumed by the Next.js frontend
- M-Pesa (Safaricom Daraja) for credit purchases and escort payouts
- Laravel Pint (code style)
- Auto-increment integer primary keys (Laravel default — this project does
  **not** use UUIDs)

Planned packages (per SOW — introduce only when the relevant phase starts):

- **Filament 4 + Filament Shield** — admin portal (Phase 1)
- **Laravel Socialite** — Google & Facebook OAuth (Phase 1)
- A TOTP/2FA package (e.g. `pragmarx/google2fa-laravel`) — 2FA (Phase 1)

> **Note on the legacy Inertia/React layer.** The repo still contains an
> Inertia + React scaffold under `resources/js` (Breeze auth pages, chat,
> dashboards). The member/escort experience is being moved to the **Next.js**
> app consuming the API; treat the Inertia pages as legacy. Do not build new
> member/escort features in Inertia — build API endpoints for the Next.js
> frontend instead. Do not delete the Inertia layer wholesale; retire it
> feature by feature as the API + Next.js equivalents land.

Do not introduce other new major packages without a strong reason.

---

## Development Philosophy

Build feature by feature, following the SOW phase order.

For every feature:

1. Read this file and the relevant SOW section before coding.
2. Follow Laravel conventions — don't reinvent what the framework provides.
3. Keep controllers thin — push logic into Services.
4. Build the smallest useful version first.
5. Refactor only when repetition or complexity appears.
6. Fix all errors before finishing a feature.
7. Every credit-moving operation must be wrapped in a DB transaction and be
   idempotent — never double-credit or double-charge.

---

## Architecture

```
app/
  Events/               <- Broadcast events (NewMessage, MessageRead, UserTyping, ConversationCreated)
  Filament/             <- Admin panel (Filament 4 split-file convention)
    Admin/                        <- panel namespace (panel id: admin-panel)
      Http/Responses/             <- custom Filament responses (e.g. LoginResponse)
      Pages/Auth/                 <- custom auth pages (e.g. MustResetPassword)
      Resources/
        EscortResource.php        <- thin Resource: model, navigation, form()/getPages() wiring
        EscortResource/
          Pages/                  <- ListEscorts, CreateEscort, EditEscort (one Livewire page each)
          Schemas/EscortForm.php  <- form schema (getSchema(): array) — extracted out of the Resource
          Tables/EscortsTable.php <- table config (configure(Table): Table) — extracted out of the Resource
        UserResource.php  + UserResource/{Pages,Schemas,Tables}/    <- System users (CRUD)
        MemberResource.php + MemberResource/{Pages,Tables}/         <- Member profiles (read-only — wallet + social login)
  Helpers/              <- FunctionHelper.php (globally autoloaded via composer.json)
  Http/
    Controllers/
      Api/              <- JSON API controllers for the Next.js frontend
        SocialAuthController.php <- Google/Facebook OAuth redirect + callback
      Auth/             <- Auth controllers (legacy Breeze)
      ApiController.php  <- Data endpoints (counties, towns, escorts, favorites, phone unlock)
      ChatController.php <- Conversation + messaging (real-time chat)
      EscortController.php
      MpesaController.php <- STK push + Daraja callbacks
    Middleware/
    Requests/           <- Form Request validation classes
    Resources/          <- (to add) API Resource transformers for JSON responses
  Models/               <- Eloquent models (integer PKs, several use SoftDeletes)
    Member.php           <- Wallet + social login for member-role users (1:1 with User)
  Policies/             <- (to add) Gate policies per model (also drive Shield)
  Providers/            <- AppServiceProvider, BroadcastServiceProvider
    Filament/           <- (to add) AdminPanelProvider
  Services/             <- Business logic (MpesaService today; add more per domain)

routes/
  api.php               <- All JSON API routes for the Next.js frontend + M-Pesa callbacks
  web.php               <- Legacy Inertia routes (being retired) + health check
  auth.php              <- Auth routes (legacy Breeze)
  channels.php          <- Broadcast channel authorization (user.{id}, conversation.{id})
  console.php

database/
  migrations/
  seeders/
  factories/

_docs/                  <- Statement of Work + internal documentation
```

The Next.js frontend lives in a **separate repository / app** and is not part
of this codebase. Coordinate field names and response shapes with it (see
"API Design Rules").

---

## Data Model

All models use **auto-increment integer primary keys** (Laravel default). Do
not introduce UUIDs. Several models use `SoftDeletes` — check the model before
assuming a hard delete.

### Core models

| Model | Purpose | Notes |
|---|---|---|
| `User` | Supertype for every actor | `user_type` discriminator (`system_user`/`escort`/`member`), role via Spatie, `SoftDeletes`. Appends `display_name`, `is_online`, `age`, `role_name`. Wallet methods (`hasSufficientCredits`, `addCredits`, `deductCredits`) delegate to `memberProfile`. Credit columns were migrated to `members` table. |
| `Member` | Member profile (1:1 with User) | **Credit wallet lives here** (`credits`, `total_credits_earned`, `total_credits_spent`, `credits_expire_at`). Also stores social login data (`social_id`, `social_provider`, `social_avatar`). `SoftDeletes`. |
| `Escort` | Escort profile (1:1 with User) | Rates, services (JSON), gallery, `is_verified`, `verification_status`, `rating`, `earnings`, `balance`, `featured`. `SoftDeletes`. |
| `EscortResource` | Photos / videos for an escort | `type`, `is_primary`, `is_verified`, `is_public`, `sort_order`. |
| `Conversation` | 1:1 chat thread between two users | Per-side mute/archive/block/read flags, `is_paid_conversation`, `total_credits_spent`, `total_earnings`, `credit_payer_id`. |
| `Message` | A chat message | Attachments, reactions (JSON), reply threading, `requires_credit`, `credit_cost`, `is_paid`, `payment_verified`, soft-delete-per-side columns. |
| `CreditTransaction` | Immutable ledger of every credit movement | `type` (purchase/usage/bonus/…), `balance_before`/`balance_after`, polymorphic `reference` via `reference_type`/`reference_id`. |
| `MpesaPayment` | A Daraja transaction | `transaction_id`, `credits_awarded`, `status`; awards credits idempotently via `MpesaService`. |
| `Review` | Client review of an escort | `rating`, `comment`, `is_verified`, `is_visible`. |
| `Favorite` | Member → escort bookmark | Static `isFavorited()` and `toggle()` helpers. |
| `County` / `Town` | Kenyan location reference data | Powers location filters. |

### Model Rules

- New models use auto-increment integer primary keys — **do not** add
  `HasUuids`.
- Always define `$fillable` explicitly — never use `$guarded = []`.
- Use `$casts` for all non-string fields (decimals, booleans, JSON, dates).
  Credit/money fields cast to `decimal:2`.
- Every user must have `user_type` set on create (`system_user`, `escort`, or
  `member`). This is the fast discriminator for queries
  (`where('user_type', 'escort')`). Fine-grained authorization stays on Spatie
  roles / Shield permissions.
- Use `$user->isSystemUser()`, `$user->isEscort()`, `$user->isMember()` helpers
  (check `user_type` column) instead of `$user->hasRole()` when determining
  *what kind of user* this is. Use `$user->hasRole()` / Shield permissions when
  determining *what the user can do*.
- Comment every relationship explaining what it links (see existing models).
- Add `SoftDeletes` to any model where records must be recoverable
  (users, escorts, transactions, reviews already use it).

---

## The Credit Economy (Core Domain)

This is the heart of the platform — treat it with care. Every rule below is
non-negotiable.

### Credit flow

1. A member buys credits → M-Pesa STK push → Daraja callback →
   `MpesaService::awardCredits()` credits the wallet and writes a `purchase`
   `CreditTransaction`.
2. A member spends credits (phone unlock, paid message, paid conversation) →
   the amount is deducted from the member and split via commission.
3. The escort receives their share (default 70%) as credited earnings; the
   platform keeps the commission (default 30%).
4. Escorts withdraw earnings via an M-Pesa B2C payout flow.

### Commission (SOW §2.1, §5.2 — to be built)

- Default split: **30% platform / 70% escort**, configurable.
- Commission is captured on **every** transaction where a client spends
  credits on an escort service.
- Example: client unlocks a phone for 10 credits → platform keeps 3, escort
  receives 7.
- Configurable env variables to introduce:
  `PLATFORM_COMMISSION_PERCENT`, `MINIMUM_WITHDRAWAL`, `CREDIT_EXPIRY_DAYS`.
  (Note: `PHONE_UNLOCK_COST` and `MESSAGE_COST` already exist in `.env`.)

### Ledger rules

- **Every** credit movement must write a `CreditTransaction` row with
  `balance_before` and `balance_after`. The ledger is the source of truth —
  never mutate a wallet without a matching ledger entry.
- All credit operations run inside `DB::transaction(...)`.
- All credit operations are **idempotent** — guard against double-processing
  (see `MpesaService::awardCredits()`, which checks for an existing
  `creditTransaction` before crediting).
- The credit wallet lives on the `Member` model, not `User`. `User` has
  convenience methods (`hasSufficientCredits()`, `addCredits()`,
  `deductCredits()`) that delegate to `$this->memberProfile`. In controllers
  and services, prefer calling them on the `User` instance — they route
  through to the `Member` wallet automatically. If you need direct wallet
  access, use `$user->memberProfile`.
- Escorts do **not** have a `Member` profile — their earnings live on the
  `Escort` model (`earnings`, `balance`).
- Enforce credit expiry via `credits_expire_at` on the `Member` model /
  `CREDIT_EXPIRY_DAYS`.

### M-Pesa integration

- Purchases (C2B / STK): `MpesaController::stkPush()`; Daraja callbacks land at
  `/api/payments/{callback,confirmation,validation,timeout,result}`.
- Payouts (B2C): `MpesaService::generateCredential()` prepares the security
  credential; build the escort withdrawal flow on top of it (SOW §2.5).
- Daraja credentials (Consumer Key/Secret, Shortcode, Passkey) are provided by
  the client — never hardcode; read from config/env.
- Callback routes are **public** (no auth) — always validate/verify the payload
  before mutating any payment or wallet.

---

## Admin Portal — Filament 4 + Filament Shield (SOW §2.3, Phase 1)

The full admin portal **must be built with Filament 4**, with **Filament
Shield** driving its roles and permissions. It is the primary Phase 1
deliverable. The panel is **admin-only** — members and escorts never access it
(they use the Next.js frontend).

### Setup (when starting Phase 1)

```bash
# Filament 4 core (already installed)
# php artisan filament:install --panels

# Filament Shield (already installed via composer)
php artisan shield:generate --all --option="policies_and_permissions" --panel=admin-panel

# assign super_admin to an existing user (non-interactive)
php artisan tinker --execute="\$user = App\Models\User::where('email', 'admin@example.com')->first(); \$user->assignRole('super_admin');"
```

### Panel access

- The panel id is `admin-panel` and it serves `/admin-panel`.
- Gate access on the `User` model:

  ```php
  public function canAccessPanel(\Filament\Panel $panel): bool
  {
      return true; // AccessPanelMiddleware + MustResetPasswordMiddleware handle finer gating
  }
  ```

- Filament Shield enforces per-resource / per-action permissions on top of that
  gate. `super_admin` bypasses all checks; scope other admin sub-roles via
  Shield's generated permissions rather than hand-written checks.

### Required admin features (SOW §2.3)

| Feature | What it does |
|---|---|
| User Management | Create, edit, suspend, ban users (`UserResource`) |
| Member Management | View member profiles, wallet, social login — **read-only** (`MemberResource`) |
| Escort Management | Full CRUD on escorts — profile, verification, rates, services (`EscortResource`) |
| Content Moderation | Handle reported content and profiles |
| Platform Analytics | Earnings, commissions, user-growth charts (use widgets) |
| Role & Permission Management | Assign and revoke roles — via **Filament Shield's** Role resource |
| System Settings | Configure platform variables (commission %, costs, expiry) from the UI |

### Filament 4 folder convention (follow exactly)

Resources follow the **Filament 4 split-file layout** already used by
`EscortResource` and `UserResource` — the `Resource` class stays thin and the
form, table, and pages each live in their own file. **Match this structure for
every new Resource; never put form or table definitions inline in the Resource
class.**

```
app/Filament/Admin/Resources/
  ReviewResource.php                       <- thin Resource (model, navigation, form()/table()/getPages())
  ReviewResource/
    Pages/
      ListReviews.php                      <- extends ListRecords; delegates table() to Tables/ReviewsTable
      CreateReview.php                     <- extends CreateRecord (omit for read-only resources)
      EditReview.php                       <- extends EditRecord (omit for read-only resources)
      ViewReview.php                       <- extends ViewRecord (for read-only/view flows, cf. ViewMember)
    Schemas/
      ReviewForm.php                       <- form schema class, static configure(Schema): Schema
    Tables/
      ReviewsTable.php                     <- table schema class, static configure(Table): Table
```

Wiring rules (see `EscortResource.php`, `EscortForm.php`, `EscortsTable.php`,
`CreateEscort.php`, `ListEscorts.php` for the reference implementation):

- The `Resource` class holds only: `$model`, `$navigationSort`, navigation
  helpers (`getModelLabel`, `getPluralModelLabel`, `getNavigationGroup`,
  `getNavigationIcon`), `getEloquentQuery()` (eager-load relations here),
  `getPages()`, and thin `form()` / `table()` methods that delegate to the
  schema classes: `return ReviewForm::configure($schema);` and
  `return ReviewsTable::configure($table);`.
- `Schemas/{Model}Form.php` — see **Filament Form Rules** below for the exact
  `configure()` + sectioned-method shape. Filament 4 imports: schema/section
  from `Filament\Schemas\*`, field components from `Filament\Forms\Components\*`.
- `Tables/{Model}sTable.php` — see **Filament Table Rules** below. Table classes
  are named plural (`EscortsTable`, `UsersTable`, `ReviewsTable`).
- `Pages/` — one class per page (`List`, `Create`, `Edit`, `View`). Custom
  create/edit logic lives here (e.g. `CreateEscort::handleRecordCreation()`
  creates the `User` + `Escort` in one `DB::transaction`).
- Namespaces mirror the folders exactly:
  `App\Filament\Admin\Resources\ReviewResource\{Pages,Schemas,Tables}`.
- Generate the scaffold, then split it into the above layout. All files land
  under the `Admin` panel namespace at `app/Filament/Admin/` (panel id
  `admin-panel`, provider `AdminPanelPanelProvider`).

> **Migration note.** The existing `EscortForm` and `UserForm` still expose the
> older `getSchema(): array` signature. New forms **must** use the
> `configure(Schema $schema): Schema` pattern below; migrate the two legacy
> forms to it when you next touch them.

### Filament conventions

- One `Resource` per model that admins manage (`UserResource`,
  `MemberResource`, `EscortResource`, `ReviewResource`,
  `CreditTransactionResource`, `MpesaPaymentResource`, etc.), each following the
  split-file layout above.
- **Read-only resources** (financial records, member profiles): override
  `canCreate`/`canEdit`/`canDelete` to return `false` and comment why; expose a
  `View` page instead of Create/Edit (see `MemberResource` + `ViewMember`).
- **EscortResource** creates both a `User` + `Escort` profile in a single
  transaction — see `CreateEscort::handleRecordCreation()`.
- Keep Resources thin: forms, tables, and infolists only, each extracted into
  its `Schemas/`, `Tables/`, `Pages/` file. Any state change that touches
  credits, verification, or bans must delegate to a **Service**, never inline
  business logic in a Filament action or page.
- Do not hand-roll role/permission checks in Resources — rely on the
  Shield-generated permissions. Only add `can*()` overrides for behaviour
  Shield does not express (e.g. immutable financial records).
- Run `php artisan shield:generate --all --panel=admin-panel` after adding any
  new Resource, Page, or Widget so its permissions exist.
- Analytics live in Filament **Widgets** (stats overviews + charts).
- Use Filament Actions for verification approve/reject and user suspend/ban;
  each action calls the corresponding Service method and records an audit trail.
- Labels use the localisation helper `__()` with `admin/*` translation keys
  (see `UserForm`) — never hardcode display strings.

---

## Filament Form Rules

Every Filament resource form must be extracted into a dedicated `{Model}Form`
schema class inside the resource's `Schemas/` folder. **Never define form fields
directly inside the Resource class**, and never inline them in a page.

Structure (already established by `EscortResource` / `UserResource`):

```
app/Filament/Admin/Resources/{Model}Resource/
  Schemas/{Model}Form.php     <- form schema class
  Tables/{Model}sTable.php    <- table schema class (plural)
  Pages/List{Model}.php
  Pages/Create{Model}.php
  Pages/Edit{Model}.php
```

Rules:

- The schema class exposes a single public static
  `configure(Schema $schema): Schema` method.
- Group fields into logical `Section` components built by `protected static`
  methods — one method per section. `configure()` only composes the sections;
  it never defines fields directly.
- Every section method has a one-line docblock (`@return Section`) explaining
  what the section contains — the intent, not just a field list.
- **Every section is invoked with `->columnSpanFull()`** inside the
  `configure()` `components()` array, so sections stack vertically at full
  width. The schema itself never sets `->columns(...)` — column layout is
  delegated to each section.
- Within a section, fields use `->columns(2)` unless a single column is
  justified (e.g. a lone rich-text/JSON field).
- **Every section must declare a title, a description, and a leading icon.** No
  unlabelled or iconless sections. Title → `Section::make(__('…'))`;
  description → `->description(__('…'))`; icon → `->icon(Heroicon::…)`. Pick a
  Heroicon that matches intent (`Heroicon::OutlinedUser` for identity,
  `Heroicon::OutlinedKey` for credentials, `Heroicon::OutlinedShieldCheck` for
  access/roles, `Heroicon::OutlinedBanknotes` for financial, etc.). An admin
  should be able to scan a form vertically and know what each block does without
  reading the fields.
- Titles, descriptions, and field labels use `__()` translation keys
  (`admin/settings.*`), consistent with `UserForm`.
- Import only the components actually used — no unused imports.
- Comment all methods with `@param` / `@return`.

Example:

```php
use App\Filament\Admin\Resources\EscortResource;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * Form schema for the Escort create/edit flow (EscortResource).
 *
 * Composes: User Account, Profile, Physical Attributes, Rates, Services,
 * Availability, Financial (read-only on edit).
 */
class EscortForm
{
    /**
     * Main form layout — composes every section at full width.
     *
     * @param  Schema  $schema
     * @return Schema
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            self::userAccountSection()->columnSpanFull(),
            self::profileSection()->columnSpanFull(),
        ]);
    }

    /**
     * Linked user account — names, email, phone, and create-only password.
     *
     * @return Section
     */
    protected static function userAccountSection(): Section
    {
        return Section::make(__('admin/settings.escort.section.account'))
            ->description(__('admin/settings.escort.section.account_hint'))
            ->icon(Heroicon::OutlinedUser)
            ->columns(2)
            ->schema([
                TextInput::make('user.first_name')->required()->maxLength(255),
                // Password only on create — hidden and non-dehydrated on edit.
                TextInput::make('user.password')
                    ->password()
                    ->hiddenOn('edit')
                    ->dehydrated(fn (?string $state): bool => filled($state)),
            ]);
    }
}
```

---

## Filament Table Rules

Every Filament table that displays time-bound records (reviews, credit
transactions, M-Pesa payments, conversations, audit logs, etc.) must use the
shared `HasDateRangeFilter` trait for date-range filtering. **Never reimplement
date-range parsing inside an individual table class.**

Location — the trait is global, one copy for the whole panel:

```
app/Filament/Concerns/HasDateRangeFilter.php
```

Any Table class that exposes a date filter must `use HasDateRangeFilter;` and
call `self::applyDateRangeFilter(...)` from inside the filter's `query()`
callback. Canonical implementation (Carbon-based, `dd/mm/yyyy - dd/mm/yyyy`):

```php
namespace App\Filament\Concerns;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

trait HasDateRangeFilter
{
    /**
     * Apply a "dd/mm/yyyy - dd/mm/yyyy" range filter to a query.
     *
     * @param  Builder  $query
     * @param  array    $data
     * @param  string   $column         Column to filter on
     * @param  int      $defaultMonths  Lookback window when no range is supplied
     * @param  int      $maxMonths      Maximum range — clamps overly wide selections
     * @return Builder
     */
    protected static function applyDateRangeFilter(
        Builder $query,
        array $data,
        string $column = 'created_at',
        int $defaultMonths = 2,
        int $maxMonths = 3,
    ): Builder {
        $raw = $data[$column] ?? null;

        if ($raw && str_contains($raw, ' - ')) {
            [$from, $to] = explode(' - ', $raw);

            $from = Carbon::createFromFormat('d/m/Y', trim($from))->startOfDay();
            $to   = Carbon::createFromFormat('d/m/Y', trim($to))->endOfDay();

            // Clamp the window to maxMonths to keep queries bounded.
            if ($from->diffInMonths($to) > $maxMonths) {
                $from = $to->copy()->subMonths($maxMonths)->startOfDay();
            }

            return $query
                ->where($column, '>=', $from)
                ->where($column, '<=', $to);
        }

        // No range supplied — fall back to a sensible default lookback.
        return $query->where($column, '>=', now()->subMonths($defaultMonths)->startOfDay());
    }
}
```

Rules:

- The trait lives at `app/Filament/Concerns/HasDateRangeFilter.php` — never
  duplicate it.
- Every paginated Table class with a date filter must `use HasDateRangeFilter;`.
- Never parse the `"dd/mm/yyyy - dd/mm/yyyy"` string inside the Table class —
  always delegate to the trait.
- Always pass an explicit `column` argument when filtering anything other than
  `created_at` (e.g. `column: 'processed_at'` for M-Pesa payments).
- Tune `defaultMonths` / `maxMonths` per table to keep result sets bounded
  (default 2 / 3 months).
- The trait clamps over-wide ranges — never bypass the clamp in the Table class.

---

## Filament Export Rules

Every paginated Filament table (reviews, credit transactions, M-Pesa payments,
conversations, escorts, members, audit logs, and any other paginated Table
class) **must** implement a Filament Export action so staff can pull bounded
result sets out as CSV/XLSX without overloading the UI.

Filament Actions ships `ExportAction` with Filament 4 — no extra
`composer require`. Setup is migrations + queue + panel notifications.

> **Status in this repo:** ❌ Not yet installed. The `job_batches`,
> `notifications`, `exports`, and `failed_import_rows` tables and the panel's
> database-notifications config are **not** present yet. Run every step below
> before adding the first `ExportAction` — exports fail without the
> `job_batches` / `exports` tables, and staff never see "Export ready" without
> the notifications migration + panel config.

### Installation & Setup

```bash
# 1. job_batches table — exports run as queued job batches
php artisan make:queue-batches-table

# 2. notifications table — staff get an in-app "Export ready" notification
php artisan make:notifications-table

# 3. Publish Filament Actions migrations (creates `exports`, `failed_import_rows`)
php artisan vendor:publish --tag=filament-actions-migrations

# 4. Run everything
php artisan migrate

# 5. Ensure a queue worker is running (queue connection is `database`)
php artisan queue:work database --queue=default --tries=3 --timeout=120
```

This project is **MySQL + integer PKs**, so the published notifications
migration needs no Postgres/UUID tweaks — the default `morphs`/`foreignId`
columns line up with the integer `users.id`.

#### Enable database notifications on the panel

`AdminPanelPanelProvider` must enable database notifications so the "Export
ready" toast is delivered. Add this to the `panel()` chain:

```php
// app/Providers/Filament/AdminPanelPanelProvider.php
return $panel
    // ...
    ->databaseNotifications()
    ->databaseNotificationsPolling('30s');
```

Without `->databaseNotifications()`, staff never see the download link.

#### Filesystem disk

Filament writes export files to the exporter's `getFileDisk()`, falling back to
`FILESYSTEM_DISK`:

- **Local dev:** leave `FILESYSTEM_DISK=local` — exports land in `storage/app/`.
- **Production:** set `FILESYSTEM_DISK=s3` (or override per-exporter with
  `->fileDisk('s3')`).

Never serve exports from the `public` disk — they contain operational/financial
data and must require authentication to download. Exports always run on the
queue (`QUEUE_CONNECTION=database`); **never** run them synchronously (`sync`),
or the request will block on the whole export.

### Generating an Exporter class

One Exporter per model, at `app/Filament/Exports/{Model}Exporter.php`:

```bash
php artisan make:filament-exporter CreditTransaction            # hand-written columns (preferred)
php artisan make:filament-exporter CreditTransaction --generate # auto columns, then prune sensitive fields
```

### Wiring the Export action into a Table

Every paginated Table class **must place the Export action inside the
`BulkActionGroup`**, alongside `DeleteBulkAction`. This is the only acceptable
position — do **not** put it in `headerActions()`/`toolbarActions()` as a
standalone button. `ExportAction` in Filament 4 already handles both modes
(full filtered query when no rows are ticked, selected rows when some are), so
one button covers both cases, which is why it belongs in the group.

Always set
`->fileName(fn (Export $export): string => "{plural-model}-{$export->getKey()}")`
so the file traces back to its `exports` row (queue batch id, user, timestamps).

```php
use App\Filament\Exports\CreditTransactionExporter;
use App\Filament\Concerns\HasDateRangeFilter;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ExportAction;
use Filament\Actions\Exports\Models\Export;
use Filament\Tables\Table;

/**
 * Table schema for the CreditTransaction resource (immutable ledger).
 *
 * The Export action lives inside the BulkActionGroup — never hoist it into a
 * standalone toolbar/header action.
 */
class CreditTransactionsTable
{
    // Shared dd/mm/yyyy date-range filter — see "Filament Table Rules".
    use HasDateRangeFilter;

    /**
     * Compose columns, filters, and the bulk-actions dropdown.
     *
     * @param  Table  $table
     * @return Table
     */
    public static function configure(Table $table): Table
    {
        return $table
            ->toolbarActions([
                BulkActionGroup::make([
                    // Ledger rows are immutable — no DeleteBulkAction here.
                    ExportAction::make()
                        ->exporter(CreditTransactionExporter::class)
                        // File name carries exports.id so support can trace it back.
                        ->fileName(fn (Export $export): string => "credit-transactions-{$export->getKey()}"),
                ]),
            ]);
    }
}
```

### Exporter class shape

Each Exporter declares its columns explicitly — never auto-export every model
attribute. Sensitive fields (passwords, tokens, raw M-Pesa credentials,
verification documents) must be excluded.

```php
namespace App\Filament\Exports;

use App\Models\CreditTransaction;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

/**
 * CSV / XLSX exporter for the CreditTransaction ledger table.
 *
 * Columns are declared explicitly; balances are exported for reconciliation,
 * never internal-only fields. Runs on the database queue per AGENTS.md.
 */
class CreditTransactionExporter extends Exporter
{
    /** Model whose rows feed the export. */
    protected static ?string $model = CreditTransaction::class;

    /**
     * Columns written to the exported file (ledger order: who, what, balances).
     *
     * @return array<int, ExportColumn>
     */
    public static function getColumns(): array
    {
        return [
            ExportColumn::make('type'),
            ExportColumn::make('amount'),
            ExportColumn::make('balance_before')->label('Balance Before'),
            ExportColumn::make('balance_after')->label('Balance After'),
            ExportColumn::make('created_at')->label('Date'),
        ];
    }

    /**
     * Notification body shown when the export completes.
     */
    public static function getCompletedNotificationBody(Export $export): string
    {
        return 'Your credit-transactions export is ready ('.$export->successful_rows.' rows).';
    }
}
```

Rules:

- Every paginated Table exposes `ExportAction` **inside the `BulkActionGroup`**
  — never in header/toolbar actions as a standalone button.
- Always set `->fileName(...)` with the `exports.id` so files trace back.
- Exporters live at `app/Filament/Exports/{Model}Exporter.php` — one per model.
- Declare columns explicitly — never expose model internals or sensitive fields.
- Exports run on the queue (`database`) — never synchronously.
- Always set `getCompletedNotificationBody()` so staff know the file is ready.

### Commenting Filament code (required on every file)

Every Filament file carries the same comment standard as Services — the existing
`EscortResource`, `EscortForm`, `EscortsTable`, and `CreateEscort` files are the
reference.

**Resources (`{Model}Resource.php`)**

- Class docblock stating which model it manages, the 1:1 relationships involved,
  and any policy/`can*()` overrides and why.
- Comment `$navigationGroup` / `$navigationSort` / `$navigationIcon` when the
  grouping is non-obvious.
- Comment `getEloquentQuery()` overrides with WHY the base query is narrowed
  (eager-load, scoping).

**Forms (`Schemas/{Model}Form.php`)**

- Class docblock stating which Resource the form belongs to and listing the
  sections it composes.
- `configure()` has a one-line docblock; every `protected static` section method
  has a one-line docblock explaining the *intent* of its fields.
- Comment any non-obvious field config: `->reactive()`, `->visible(fn …)`,
  `->dehydrated(false)`, `->disabled()`, custom `->rules()`, computed defaults
  (see the `hiddenOn('edit')` password and `visible(operation === 'edit')`
  financial section in `EscortForm`).

**Tables (`Tables/{Model}sTable.php`)**

- Class docblock stating the table's purpose and what it composes (columns /
  filters / row actions / bulk actions).
- `configure()` docblock explaining default sort and any `getEloquentQuery` /
  eager-load choices.
- Inline `//` comments on non-obvious columns (badge colour logic, toggled-off
  columns, relation columns that avoid N+1), on each filter, and on every
  row/bulk Action stating which Service method it delegates to.

**Widgets (`app/Filament/Admin/Widgets/*Widget.php`)**

- Class docblock stating what the widget shows.
- Docblock on `getStats()` / `getData()` explaining the data source and refresh
  cadence; comment `$pollingInterval` with WHY that interval (query cost vs.
  staleness).

**Exporters (`app/Filament/Exports/{Model}Exporter.php`)**

- Class docblock stating which table consumes it and reiterating that sensitive
  fields are never exported.
- `getColumns()` docblock describing the column-order rationale.

**Pages (`Pages/{List,Create,Edit,View}{Model}.php`)**

- Class docblock only required when the page overrides default behaviour
  (`handleRecordCreation`, `mutateFormDataBeforeSave`, `getHeaderActions`, etc.).
- Comment every override with the reason and a WHY comment on non-obvious steps
  (transactions, splitting `user.*` fields, role assignment — see `CreateEscort`).
  A stock page subclass with no overrides needs no class docblock.

Summary — Resource, Form, Table, Widget, Exporter, and overridden Page classes
all get a class docblock; every `protected static` builder method gets a one-line
docblock; every action notes which Service it delegates to; every non-default
lifecycle hook (`->visible`, `->reactive`, `->dehydrated`, `getEloquentQuery`,
etc.) gets an inline `//` WHY comment.

---

## API Design Rules (for the Next.js frontend)

The member/escort frontend is a **separate Next.js app** that consumes this
backend over JSON. All new member/escort functionality is built as API
endpoints — never as Inertia pages.

### Structure — every new endpoint follows this pattern

```
app/Http/Requests/StoreReviewRequest.php        # 1. validation
app/Http/Resources/ReviewResource.php            # 2. response transformation
app/Services/Reviews/ReviewService.php           # 3. business logic
app/Http/Controllers/Api/ReviewController.php     # 4. thin controller
routes/api.php                                    # 5. route registration
```

### Controller rules

Controllers stay thin. They:
- Receive the request (via a Form Request when input needs validation),
- Call a Service,
- Return an API Resource or JSON response.

Never put credit math, commission logic, verification, or payment handling
inside a controller — that belongs in a Service.

### Form Requests

Every POST/PATCH/PUT/DELETE that takes user input must use a Form Request for
validation. Do not validate inline with `$request->validate()` for non-trivial
input.

### API Resources & response shape

- Every endpoint that returns model data uses an API Resource — never return a
  raw model or `$model->toArray()`.
- Wrap responses in a `data` key (single or collection); use
  `ResourceCollection` for paginated lists.
- Match field names to what the Next.js frontend expects — coordinate response
  shapes with the frontend before building. Never leak sensitive fields
  (passwords, tokens, raw M-Pesa credentials, private verification documents).

### Services

Business logic lives in `app/Services/`, organised by domain
(`MpesaService` exists; add `CreditService`, `CommissionService`,
`WithdrawalService`, `ChatService`, `EscortVerificationService`, `ReviewService`,
etc. as their features are built). Services are injected into API controllers
and Filament actions via the constructor. If logic is shared between the API and
the admin panel, keep it in one Service and inject it from both.

### Routing & CORS

- All frontend-facing endpoints live in `routes/api.php` under `/api`
  (version them, e.g. `/api/v1`, if the frontend expects it).
- Protected endpoints use `auth:sanctum`. Public data endpoints
  (`/api/data/counties`, `/api/data/towns`) and M-Pesa callbacks
  (`/api/payments/*`) stay public.
- The Next.js app runs on a different origin — configure `config/cors.php` to
  allow it and permit `Authorization` + `Content-Type` headers.

---

## Real-Time Messaging & Chat

Chat is a first-class feature and a monetization surface (paid messages / paid
conversations). The Next.js frontend subscribes to broadcasts via Laravel Echo.

- `ChatController` owns conversations and messages; broadcasts `NewMessage`,
  `MessageRead`, `UserTyping`, `ConversationCreated` events.
- Conversations carry paid-chat state (`is_paid_conversation`, `credit_payer_id`,
  `total_credits_spent`, `total_earnings`); messages carry paywall state
  (`requires_credit`, `credit_cost`, `is_paid`, `payment_verified`).
- **Paid messages / conversations (SOW §2.4):** lock message content behind a
  credit paywall, verify payment before revealing content, and record the
  transaction in `credit_transactions` (with commission split). Never send
  locked content to the client until `payment_verified` is true.
- **Messaging improvements (SOW §2.9):** file/image attachments (the `Message`
  attachment columns already exist), real-time typing indicators (`UserTyping`
  event is wired), and message reactions (`reactions` JSON column exists).
- Per-side flags (mute/archive/block/delete) are stored as `user_one_*` /
  `user_two_*` columns — resolve the current user's "side" before reading them.
- Channel authorization lives in `routes/channels.php` (`user.{id}`,
  `conversation.{id}`) and is enforced for Sanctum-authenticated frontend users.

---

## Auth & Security Rules

- **Frontend (members & escorts):** authenticate via **Laravel Sanctum** tokens
  issued to the Next.js app. All protected API routes use `auth:sanctum`.
- **Admin:** authenticate through the **Filament** panel session; access is
  gated by `canAccessPanel()` + **Filament Shield** permissions.
- The `users.user_type` column (`system_user` / `escort` / `member`) is a
  **fast discriminator** for queries — use it when you need "find all escorts"
  or "count members". Roles are enforced with **Spatie Laravel Permission**
  (`super_admin`, `admin`, `manager`, `escort`, `member`, etc.), surfaced in
  the admin UI by Shield. Use `hasRole()`, policies, and Shield permissions for
  authorization gates — never check role strings ad hoc. The two work together:
  `user_type` for type, Spatie roles for permission.
- Members and escorts must **never** reach Filament. Members must never reach
  escort-only earnings/withdrawal endpoints. Enforce with policies +
  token abilities.
- **Social login (SOW §2.7):** add Laravel Socialite for Google & Facebook
  OAuth on the frontend auth flow. Credentials (Client ID/Secret, Redirect URI)
  are provided by the client — read from `config/services.php` / env, never
  hardcode.
- **2FA (SOW §2.8):** optional TOTP-based 2FA with authenticator-app support
  and recovery codes.
- M-Pesa callback routes are public — always verify payloads before acting.
- Never log or expose secrets (M-Pesa keys, OAuth secrets, Pusher secret).

---

## Broadcasting, Queue & Cache

- **Broadcasting:** Pusher is the configured driver (`BROADCAST_DRIVER=pusher`);
  Laravel Reverb is also available (`config/reverb.php`). The Next.js frontend
  connects with Laravel Echo. Channel authorization lives in
  `routes/channels.php`.
- **Queue:** `QUEUE_CONNECTION=database` by default. Long-running work (M-Pesa
  reconciliation, payout processing, email batches, Filament exports) must be
  dispatched as queued jobs — never run inline in a controller or Filament
  action.
- **Cache/Session:** `database` drivers by default; Redis config is present if
  scaled up. Don't assume a specific driver — read from config.

---

## Error & Response Conventions

All API error responses use consistent shapes:

```json
// Validation error (422)
{ "message": "The given data was invalid.", "errors": { "field": ["..."] } }

// Business logic error (409)
{ "message": "Insufficient credits." }

// Not found (404)
{ "message": "Escort not found." }
```

- Use `findOrFail()` on protected lookups — it returns 404 automatically.
- Success responses wrap data in a `data` key via API Resources.

---

## Escort Registration & Approval (SOW §2.2)

- Public escort registration endpoint (`POST /api/.../escort/register`) consumed
  by the Next.js frontend.
- New applications create a `User` with the `escort` role and an `Escort`
  profile in `verification_status = 'pending'`.
- Admin approval queue in **Filament** — approve/reject with notification to the
  applicant. Approval flips `is_verified` / `verification_status` via an
  `EscortVerificationService`, never inline.

---

## Reviews System (SOW §2.6)

- Client review submission endpoint → `Review` (`rating`, `comment`).
- On create/update/delete, recompute the escort's aggregate via
  `Escort::updateRating()` (updates `rating` and `review_count`).
- "Report inappropriate review" flow feeds the admin content-moderation queue
  in Filament; moderation toggles `is_visible` / `is_verified`.

---

## Code Comment Rules

Comment code following the patterns already in this project (see
`MpesaService.php`, `Escort.php`, `Conversation.php`).

- Every class has a one-line docblock explaining its responsibility.
- Every public Service/Controller method has a docblock with `@param` and
  `@return`.
- Non-obvious logic (credit math, commission split, idempotency guards, M-Pesa
  encryption, per-side chat flags) gets an inline `//` comment explaining WHY.
- Relationship methods have a comment explaining what they link.
- Use `/* ── Section ── */` dividers to group methods inside long classes.
- Filament Resources and their split files (`Schemas/{Model}Form`,
  `Tables/{Model}sTable`, `Pages/*`) and Widgets get the same comment standard
  as Services — class docblock + per-method docblocks + WHY comments on any
  non-default action, policy override, transaction, or query narrowing. See
  `EscortResource`, `EscortForm`, `EscortsTable`, and `CreateEscort` as the
  reference for both the folder layout and the comment density.

---

## Project Phases (SOW §6)

Build in phase order — each phase is a payment milestone.

| Phase | Features | Duration |
|---|---|---|
| **1 — Admin Portal** | Filament 4 admin panel (+ Shield), Social login (Google & Facebook), 2FA | 2.5 wks |
| **2 — Core Systems** | Credit/Commission system, M-Pesa withdrawals, Escort registration & approval | 2 wks |
| **3 — Monetization** | Paid messages, paid conversations, escort earnings dashboard | 2 wks |
| **4 — UI & Polish** | CSS fixes, responsive design, reviews system, messaging improvements, Terms & Privacy pages | 1.5 wks |

**Out of scope (SOW §3):** mobile apps, drafting legal text, domain/hosting
setup, third-party API costs, post-warranty maintenance.

**Terms & Privacy (SOW §2.11):** build and style dedicated Terms of Use and
Privacy Policy pages (on the Next.js frontend); legal text is supplied by the
client. Link both in the footer, registration, and login flows.

---

## Build & Quality Rules

```bash
composer run dev            # serve + queue + logs + vite (concurrently)
php artisan serve           # dev server only
php artisan migrate         # run migrations
php artisan db:seed         # seed database
php artisan route:list      # verify routes
php artisan shield:generate --all --option="policies_and_permissions" --panel=admin-panel   # regenerate Filament permissions after new resources
php artisan tinker          # REPL
./vendor/bin/pint           # fix code style (run before finishing)
php artisan test            # run test suite (PHPUnit)
```

Every new feature must:
- Validate input via a Form Request (for non-trivial input).
- Keep business logic in a Service — controllers and Filament actions stay thin.
- Wrap every credit/payment operation in a DB transaction and make it idempotent.
- Write a `CreditTransaction` ledger row for every wallet movement.
- Be registered on the correct route surface with correct middleware/roles.
- For admin: follow the split-file layout (thin Resource + `Schemas/{Model}Form`
  `configure()` + `Tables/{Model}sTable` `configure()` + `Pages/`); every form
  section carries a title + description + Heroicon and stacks `columnSpanFull()`;
  every paginated table uses the `HasDateRangeFilter` trait and exposes an
  `ExportAction` inside its `BulkActionGroup`; run
  `shield:generate --all --option='policies_and_permissions' --panel=admin-panel`
  and keep Resources permission-gated.
- Pass `./vendor/bin/pint` and the existing test suite.

---

## Communication Style

Be concise. Explain what changed, which migration to run, and how to test
(curl/Postman for API endpoints + M-Pesa callbacks, or which admin role to log
in as for Filament flows).

---

## Final Reminder

Before every implementation:
- Read this file and the relevant SOW section (`_docs/RAHA-TELE_Statement_of_Work.pdf`).
- Member/escort features are **API endpoints** for the Next.js frontend
  (Form Request → Service → Controller → API Resource → route). Never build
  them as Inertia pages.
- Admin features are built in **Filament 4**, with **Filament Shield** driving
  roles/permissions. The panel is admin-only. Use the split-file layout: thin
  Resource + `Schemas/{Model}Form::configure()` (sectioned, every section with
  title + description + Heroicon + `columnSpanFull()`) + `Tables/{Model}sTable`
  (`HasDateRangeFilter` trait + `ExportAction` inside the `BulkActionGroup`) +
  `Pages/`. Comment every Filament class like a Service.
- New tables/models use auto-increment integer primary keys — **no UUIDs**.
- Credit wallet lives on the `Member` model, not `User`. `User` delegates wallet methods to `memberProfile`.
- Every credit movement is transactional, idempotent, and ledgered.
- Enforce roles with Spatie + Shield; never leak escort-only or admin-only
  actions to members, and never let members/escorts reach Filament.
- Use `user_type` column (`system_user`/`escort`/`member`) for fast queries by
  actor kind; use Spatie roles / Shield permissions for authorization gates.
- Match API response shapes to what the Next.js frontend expects; wrap in `data`.
- Run Pint (and `shield:generate --all --option='policies_and_permissions' --panel=admin-panel` after new admin resources) before finishing.
