# SOW Implementation Status

> Status assessment as of Aug 19, 2026 — mapping the RAHA-TELE codebase against the Statement of Work v1.0 (April 23, 2026). Updated to track the Phase 5 frontend-UI effort.
>
> **Update Aug 4, 2026:** Phase 2 fully completed. Credit & Commission system, M-Pesa B2C withdrawals, public escort self-registration, the Filament approval queue (with navigation badge), approve/reject notifications, `EscortVerificationService`, and the per-escort commission ledger history are all in place. Only the Inertia frontend screens remain (Phase 5).
>
> **Update Aug 19, 2026:** Escort profile pages (`/escort/{id}`) now render for seeded escorts (the route gate used a Spatie role check instead of the `user_type` discriminator, so role-less seeded escorts 404'd). `display_name` now correctly surfaces the escort stage name. Reviews system completed on the Inertia frontend: list on Escort show, write / edit / delete / report all wired; deleting a review then writing a new one works (soft-deleted row is restored instead of hitting the unique constraint); the "Write a Review" button is hidden once a member has already reviewed an escort (`meta.has_reviewed`).

## Phase Completion Summary

| Phase | Overall | Key Gaps |
|-------|---------|----------|
| **1 — Admin Portal** | ✅ 100% | All items implemented |
| **2 — Core Services** | ✅ 100% | All items implemented (backend); Inertia frontend screens tracked in Phase 5 |
| **3 — Monetization** | ✅ 100% | All items implemented |
| **4 — UI & Polish** | ~90% | CSS/responsive audit (Inertia frontend), Terms & Privacy pages (blocked on client legal text) |
| **5 — Frontend UI (Inertia)** | 🔨 In progress (~60%) | Done: token bridge, Login, 2FA challenge, Logout, social login buttons, 2FA settings screen, escort profile pages, reviews (list/write/edit/delete/report). Remaining: chat monetization, earnings, withdrawals, escort self-registration |

---

## Phase 1 — Admin Portal (2.5 wks)

### Filament 4 Admin Panel

| Item | Status | Notes |
|------|--------|-------|
| Panel setup + Shield | ✅ Done | `/admin-panel`, database notifications, Shield integrated |
| `UserResource` | ✅ Done | CRUD with suspend/activate/reset actions, Export, date filter |
| `EscortResource` | ✅ Done | Full CRUD, User+Escort created in single transaction |
| `MemberResource` | ✅ Done | Read-only View page with wallet/social login infolist |
| `ReviewResource` | ✅ Done | CRUD with moderation actions (verify/hide) |
| `CreditTransactionResource` | ✅ Done | Read-only ledger view with Export + date filter |
| `MpesaPaymentResource` | ✅ Done | Read-only payment records view |
| `ConversationResource` | ✅ Done | Read-only chat thread management |
| `EscortMediaResource` | ✅ Done | Media/gallery CRUD (named EscortMedia to avoid naming collision) |
| `SystemSettingResource` | ✅ Done | Platform settings CRUD from the UI |
| `ReportResource` | ✅ Done | Reported content moderation |
| Analytics widgets | ✅ Done | 3 exist: `PlatformStatsOverview`, `UserGrowthChart`, `RevenueChart` |
| Exporters (User, Member, Escort, Review, CreditTransaction, MpesaPayment, Conversation, SystemSetting, Report, EscortMedia) | ✅ Done | 10 exporters exist — one per resource |
| `HasDateRangeFilter` trait | ✅ Done | Applied to User/Escort tables |

### Social Login (Google & Facebook OAuth)

| Item | Status | Notes |
|------|--------|-------|
| Socialite installed | ✅ Done | `"laravel/socialite": "^5.28"` |
| `SocialAuthController` | ✅ Done | `redirect()` + `callback()` for Google/Facebook |
| Routes (`/api/auth/{provider}/...`) | ✅ Done | Member creation + 20 welcome credits + Sanctum token |
| Config (`config/services.php`) | ✅ Done | Google/Facebook keys + `socialite.redirect_frontend` |

---

## Phase 2 — Core Systems (2 wks)

### Credit & Commission System

| Item | Status | Notes |
|------|--------|-------|
| `Member` wallet (credits, totals, expiry) | ✅ Done | `hasSufficientCredits()`, `addCredits()`, `deductCredits()` |
| `CreditTransaction` model | ✅ Done | Immutable ledger, polymorphic reference, scopes |
| M-Pesa → credit purchase flow | ✅ Done | STK push → callback → `awardCredits()` (idempotent) |
| **Commission split (30/70)** | ✅ **Done** | `PhoneUnlockService` applies 30/70 split (via `ChatCreditService` pattern) |
| **`usage` CreditTransactions** | ✅ **Done** | Written by `ChatCreditService` for chat and `PhoneUnlockService` for phone unlock (polymorphic reference to `Escort`) |
| **Escort crediting on spend** | ✅ **Done** | `ChatCreditService` credits escorts on paid messages; `PhoneUnlockService` credits escorts on phone unlock |
| `CreditService` | ✅ **Done** | `app/Services/Credit/CreditService.php` — centralises wallet/ledger writes (`spendCredits`, `creditEscort`, `expireCredits`, `writeLedger`); Chat/PhoneUnlock now inject it |
| `CommissionService` | ✅ **Done** | `app/Services/Commission/CommissionService.php` — reads `config('system_settings.platform_commission_percent')` (30/70); replaces the hardcoded 30% const in `ChatCreditService`/`PhoneUnlockService` |
| `PLATFORM_COMMISSION_PERCENT` env | ✅ **Done** | Added to `.env` + `.env.example` |
| `CREDIT_EXPIRY_DAYS` env | ✅ **Done** | Added to `.env` + `.env.example`; expiry enforcement job added |
| `CREDIT_VALUE_KES` env/config | ✅ **Done** | Added to `config/system_settings.php` + `.env` (default 5) — used to convert escort credits → KES for B2C payouts |
| Credit expiry enforcement | ✅ **Done** | `credits_expire_at` set on purchase in `MpesaService::awardCredits()`; `credits:expire` command (`app/Console/Commands/ExpireCredits.php`) runs daily via `routes/console.php`, zeroes expired wallets and writes `expiry` ledger rows |
| **Escort earnings ledger history** | ✅ **Done** | `CreditService::creditEscort()` now writes a per-escort `'commission'` ledger row (user_id = escort, balance = `Escort.balance`) for every spend flow (phone unlock, paid message, message unlock). `GET /api/earnings/transactions` now shows commissions + withdrawals; added `CreditTransaction::scopeCommissions()` |

### M-Pesa Withdrawals (B2C Payouts)

| Item | Status | Notes |
|------|--------|-------|
| `generateCredential()` | ✅ Done | B2C security credential preparation |
| B2C config in `config/services.php` | ✅ Done | `b2c_shortcode`, `b2c_command_id`, etc. |
| Withdrawal request model/table | ✅ **Done** | `withdrawals` migration + `Withdrawal` model (pending → processing → completed/failed, soft-deletes, `mpesa_reference` correlation) |
| B2C payout endpoint | ✅ **Done** | `MpesaService::sendB2CPayout()` + public callbacks `MpesaController::b2cResult`/`b2cTimeout` at `/api/payments/b2c/{result,timeout}`, correlated by `OriginatorConversationID` and settled idempotently via `WithdrawalService::processB2CResult()` |
| Withdrawal UI (API or Filament) | ✅ **Done** | API: `POST/GET /api/withdrawals` (`WithdrawalController` + `WithdrawalResource`); Filament: `WithdrawalResource` with Approve / Mark-Failed-and-Refund actions + `WithdrawalExporter` |
| `MINIMUM_WITHDRAWAL_CREDITS` env | ✅ **Done** | Added to `.env` + `.env.example` |

### Escort Registration & Approval

| Item | Status | Notes |
|------|--------|-------|
| Admin creation via Filament | ✅ Done | `CreateEscort` creates User+Escort in transaction |
| `Escort` model (`verification_status`, `is_verified`) | ✅ Done | |
| `UserObserver` (welcome email) | ✅ Done | |
| **Public `POST /api/escort/register`** | ✅ **Done** | `EscortRegistrationService` + `EscortAuthController` + `RegisteredEscortResource` + `StoreEscortRegistrationRequest`. Public route (no auth); creates User (`escort` role, auto-verified) + pending Escort in one transaction; returns Sanctum token + user (201) |
| **Approval queue in Filament** | ✅ **Done** | `EscortResource::getNavigationBadge()` shows pending-application count in the nav; `EscortsTable` verification-status filter; Verify/Unverify actions on `ViewEscort` |
| **Notification on approve/reject** | ✅ **Done** | `EscortVerificationMail` + `mail/admin/escort-verification.blade.php`; queued on verify/reject (subject/body localised via `admin/mail.escort_verification.*`) |
| **`EscortVerificationService`** | ✅ **Done** | `app/Services/Escort/EscortVerificationService.php` — `verify()` / `reject($escort, $reason)` own the transaction + email; `ViewEscort` actions and all verification state changes delegate to it; inline `verification_status`/`is_verified` fields in `EscortForm` are now read-only |

---

## Phase 3 — Monetization (2 wks)

### Paid Messages & Paid Conversations

| Item | Status | Notes |
|------|--------|-------|
| `Message` credit fields (data model) | ✅ Done | `requires_credit`, `credit_cost`, `is_paid`, `payment_verified` exist |
| `Conversation` credit fields (data model) | ✅ Done | `is_paid_conversation`, `total_credits_spent`, `total_earnings`, `credit_payer_id` exist |
| **Credit enforcement on send** | ✅ Done | `Api/ChatController::sendMessage()` checks and deducts credits (sender-pays for members, locked for escorts) |
| **Payment verification flow** | ✅ Done | `POST /api/chat/messages/{message}/unlock` — member pays to reveal locked content |
| **Commission split on paid messages** | ✅ Done | 30/70 split in `ChatCreditService` for both send-time and unlock payments |

### Escort Earnings Dashboard

| Item | Status | Notes |
|------|--------|-------|
| `Escort` `earnings`/`balance` fields | ✅ Done | Data model exists |
| **Earnings API endpoint** | ✅ Done | `GET /api/earnings` + `GET /api/earnings/transactions` |
| **Earnings Filament tab** | ✅ Done | Livewire table in ViewEscort Earnings tab |
| **Transaction history view** | ✅ Done | Both API (paginated) and Filament (Livewire table with search/filter) |

---

## Phase 4 — UI & Polish (1.5 wks)

### Reviews System

| Item | Status | Notes |
|------|--------|-------|
| `Review` model | ✅ Done | `rating`, `comment`, `is_verified`, `is_visible`, scopes |
| `Escort::updateRating()` | ✅ Done | Recalculates aggregate rating/review_count |
| **ReviewController (API)** | ✅ **Done** | 6 endpoints: index, store, show, update, destroy, report |
| **Review API routes** | ✅ **Done** | 6 routes in `routes/api.php` (public + auth:sanctum); index returns `meta.has_reviewed` for the current member |
| **ReviewResource (Filament)** | ✅ Done | Admin moderation UI with verify/hide actions |
| **Report inappropriate review** | ✅ **Done** | `POST /api/reviews/{review}/report` creates Report row linked to the review |
| **ReviewService** | ✅ **Done** | Transactional CRUD + report logic in `app/Services/Review/ReviewService.php`; recreating a deleted review restores the soft-deleted row instead of hitting the unique `(user_id, escort_id)` constraint |

### Messaging Improvements

| Item | Status | Notes |
|------|--------|-------|
| Typing indicators (`UserTyping` event + endpoint) | ✅ Done | |
| Message reactions (model + helpers + API) | ✅ **Done** | `POST/DELETE /api/chat/messages/{message}/reactions`, broadcasts to conversation channel |
| File/image attachments (model + upload + API) | ✅ **Done** | Multipart upload in `sendMessage()`, stored on public disk, validated (10MB, jpg/png/gif/webp/mp4/mp3/ogg/pdf/doc), broadcast with `NewMessage` |

### Two-Factor Authentication (2FA)

| Item | Status | Notes |
|------|--------|-------|
| TOTP package installed | ✅ **Done** | `pragmarx/google2fa-laravel:^0.3.0` installed |
| 2FA config/setup | ✅ **Done** | Secret generation, inline SVG QR (via `pragmarx/google2fa-qrcode`), enable/confirm/disable via API + TOTP code verification |
| Recovery codes | ✅ **Done** | 8 codes generated on enable, consumed one-by-one, stored encrypted |

### CSS Fixes & Responsive Design

| Item | Status | Notes |
|------|--------|-------|
| Cross-browser/mobile audit | ❌ Not started | Applies to Inertia frontend (`resources/js`) primarily |

### Terms of Use & Privacy Policy

| Item | Status | Notes |
|------|--------|-------|
| Pages (Inertia frontend) | ❌ Deferred | Legal text must be provided by client per SOW §4 |

---

## Phase 5 — Frontend UI (Inertia) — connect the Inertia app to the API

> The member/escort experience lives in the **Inertia + React app at
> `resources/js`** inside this codebase. Phase 5 wires those screens to the
> existing Sanctum **JSON API endpoints in `routes/api.php`**.
>
> **Auth bridge (prerequisite):** Inertia authenticates via **session**, but the
> API endpoints require **Sanctum Bearer tokens**. Phase 5 must issue the
> session user a Sanctum token and have `resources/js/Utils/xios.jsx` send
> `Authorization: Bearer <token>` on requests. Stale `/api/conversations` /
> `/api/messages` calls in `useChat.ts` / `ChatContext.jsx` must be repointed to
> the real `/api/chat/*` routes.
>
> ✅ **Done (Aug 7, 2026):** `Utils/xios.jsx` now attaches
> `Authorization: Bearer <token>` + `X-CSRF-TOKEN`; Sanctum enabled on `User`
> (`HasApiTokens`), `personal_access_tokens` migration run; backend
> `POST /auth/bridge` (`SessionBridgeController`) swaps the token into the
> session; helper `Utils/auth.js` (`login`/`verify2fa`/`recovery2fa`/
> `apiLogout`/`completeAuth`/`ensureSessionToken`).
>
> ✅ **Session→token (Aug 7, 2026):** session-authenticated users mint a
> Sanctum token via `POST /auth/issue-token` (`SessionTokenController`); the
> app calls `ensureSessionToken()` on boot, so authed API calls work even when
> login went through the session (no stored token yet).

| UI screen / component | API endpoint(s) it consumes | Status |
|---|---|---|
| Auth: Login (`Pages/Auth/Login.jsx`) | `POST /api/auth/login` | ✅ Done — posts via `Utils/auth.js`; on `two_factor_required` stores the temp token and visits `/login/two-factor`; otherwise `completeAuth()` → `/auth/bridge` → `/dashboard`; maps 401/422 errors |
| Auth: 2FA challenge (`Pages/Auth/TwoFactorChallenge.jsx`) | `POST /api/auth/2fa/verify`, `POST /api/auth/2fa/recovery` | ✅ **Done** — `/login/two-factor` guest route; TOTP + recovery modes |
| Auth: Logout (NavBar) | `POST /api/auth/logout` | ✅ **Done** — `apiLogout()` revokes the Sanctum token, then `router.post(route("logout"))` ends the session |
| Auth: Social login buttons (Login/Register) | `GET /api/auth/{provider}/redirect` | ✅ **Done** — `Components/Auth/SocialButtons` on Login + Register; OAuth callback lands on `Auth/SocialCallback` (`POST /auth/bridge` then `/dashboard`) |
| Settings: 2FA management (`Pages/Settings/Security.jsx`) | `GET/POST /api/auth/2fa/status·enable·confirm·disable` | ✅ **Done** — enable (QR + secret + recovery codes + confirm) / disable; linked from NavBar via `security.settings`. QR rendered locally as SVG data URI via `pragmarx/google2fa-qrcode` (deprecated Google Chart API removed); inputs styled for visible placeholders; step-by-step "How to enable 2FA" card; ensures a Sanctum token before calling the authed API |
| Escort: profile view (`/escort/{id}`) | session route → `EscortController@show` | ✅ **Done** — no longer 404s for role-less seeded escorts; gate uses the `user_type` discriminator; `display_name` shows the stage name |
| Reviews: list on Escort show (`API /api/escorts/{id}/reviews`) | `GET /api/escorts/{escort}/reviews` | ✅ **Done** — list fetched from API, visible+verified only; `meta.has_reviewed` returned for the current member |
| Reviews: write / edit / delete / report | `POST /api/reviews`, `PUT/DELETE /api/reviews/{review}`, `POST /api/reviews/{review}/report` | ✅ **Done** — full flow wired; delete-then-recreate restores the soft-deleted row; "Write a Review" hidden once the member has reviewed |
| Chat: send + file attachments | `POST /api/chat/messages` | ⚠️ Send works; attachments are stubs |
| Chat: unlock locked message (paywall) | `POST /api/chat/messages/{message}/unlock` | ❌ To build |
| Chat: message history | `GET /api/chat/conversations/{conversation}/messages` | ⚠️ Covered by session route; repoint |
| Chat: reactions (render + add/remove) | `POST/DELETE /api/chat/messages/{message}/reactions` | ❌ To build |
| Escorts: phone unlock (rewire `CallModal`) | `POST /api/escorts/{escort}/unlock-phone` | ⚠️ Uses session `phone.unlock`; repoint |
| Escort: earnings dashboard + history | `GET /api/earnings`, `GET /api/earnings/transactions` | ❌ To build |
| Escort: withdrawal form + history | `POST/GET /api/withdrawals` | ❌ To build (API exists since Aug 4, 2026) |
| Escort: self-registration form | `POST /api/escort/register` | ❌ To build (API exists since Aug 4, 2026) |

### Remaining — not done in Phase 5

| Screen / component | API endpoint(s) | Status |
|---|---|---|
| Chat: file attachments | `POST /api/chat/messages` | ⚠️ Attachments are stubs |
| Chat: unlock locked message (paywall) | `POST /api/chat/messages/{message}/unlock` | ❌ Not built |
| Chat: message history | `GET /api/chat/conversations/{conversation}/messages` | ⚠️ Repoint from session route |
| Chat: reactions (render + add/remove) | `POST/DELETE /api/chat/messages/{message}/reactions` | ❌ Not built |
| Escorts: phone unlock (`CallModal`) | `POST /api/escorts/{escort}/unlock-phone` | ⚠️ Repoint from session `phone.unlock` |
| Escort: earnings dashboard + history | `GET /api/earnings`, `GET /api/earnings/transactions` | ❌ Not built |
| Escort: withdrawal form + history | `POST/GET /api/withdrawals` | ❌ Not built |
| Escort: self-registration form | `POST /api/escort/register` | ❌ Not built |

### Blocked on Phase 2 backend (no endpoint yet)

These Inertia screens cannot be built until the underlying API lands:

| Screen | Blocking gap |
|--------|--------------|
| Escort self-registration form | None — `POST /api/escort/register` exists since Aug 4, 2026 |

> **Update Aug 4, 2026:** The *Escort withdrawal form* is no longer blocked — the B2C withdrawal model, API endpoints (`POST/GET /api/withdrawals`), and Filament approval flow now exist. Only the Inertia screen needs to be wired up.

---

## Project Timeline (SOW §6)

| Phase | Features | Duration | Payment Milestone |
|-------|----------|----------|-------------------|
| **1 — Admin Portal** | Filament admin panel, Social Login | 2.5 wks | 40% (Mid-Project, with Phase 2) |
| **2 — Core Systems** | Credit/Commission, M-Pesa withdrawals, Escort registration & approval | 2 wks | 40% (Mid-Project, with Phase 1) |
| **3 — Monetization** | Paid messages/conversations, Earnings dashboard | 2 wks | 30% (Final Delivery) |
| **4 — UI & Polish** | CSS fixes, Reviews, Messaging improvements, 2FA, Terms & Policy | 1.5 wks | 30% (Final Delivery) |
| **5 — Frontend UI (Inertia)** | Wire Inertia app to `/api/*` endpoints: token bridge, social login, reviews, chat monetization, 2FA settings, earnings | — | — |

**Total: 8 weeks — $2,500 USD**

---

## Client Responsibilities (SOW §4)

| Item | Needed By | Status as of July 15 |
|------|-----------|---------------------|
| Google OAuth Credentials | Phase 1 Start | Configured in `.env` |
| Facebook OAuth Credentials | Phase 1 Start | Configured in `.env` |
| M-Pesa API Keys | Phase 2 Start | Needs confirmation |
| Terms of Use Content | Phase 4 Start | Not provided |
| Privacy Policy Content | Phase 4 Start | Not provided |
| Staging Server Access | Project Start | Needs confirmation |
