# SOW Implementation Status

> Status assessment as of July 24, 2026 — mapping the RAHA-TELE codebase against the Statement of Work v1.0 (April 23, 2026).

## Phase Completion Summary

| Phase | Overall | Key Gaps |
|-------|---------|----------|
| **1 — Admin Portal** | ✅ 100% | All items implemented |
| **2 — Core Systems** | ~30% | Credit spending/commission still broken for phone unlock, no B2C withdrawals, no public escort registration |
| **3 — Monetization** | ✅ 100% | All items implemented |
| **4 — UI & Polish** | ~90% | CSS/responsive audit (Next.js frontend), Terms & Privacy pages (blocked on client legal text) |

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
| **Commission split (30/70)** | ⚠️ **Partial** | ✅ `ChatCreditService` splits for paid messages; ❌ `unlockPhone()` still destroys credits |
| **`usage` CreditTransactions** | ⚠️ **Partial** | ✅ Written by `ChatCreditService` for chat; ❌ no `usage` tx written by `unlockPhone()` |
| **Escort crediting on spend** | ⚠️ **Partial** | ✅ `ChatCreditService` credits escort earnings on paid messages; ❌ `unlockPhone()` skips it |
| `CreditService` | ❌ Missing | No general service layer; chat logic lives in `ChatCreditService` |
| `CommissionService` | ❌ Missing | `ChatCreditService` has hardcoded 30% constant; no reusable service |
| `PLATFORM_COMMISSION_PERCENT` env | ⚠️ Config exists | In `config/system_settings.php:21` but ❌ not in `.env` |
| `CREDIT_EXPIRY_DAYS` env | ⚠️ Config exists | In `config/system_settings.php:31` but ❌ not in `.env`; no expiry enforcement job |
| Credit expiry enforcement | ❌ Missing | `credits_expire_at` field exists but unused |

### M-Pesa Withdrawals (B2C Payouts)

| Item | Status | Notes |
|------|--------|-------|
| `generateCredential()` | ✅ Done | B2C security credential preparation |
| B2C config in `config/services.php` | ✅ Done | `b2c_shortcode`, `b2c_command_id`, etc. |
| Withdrawal request model/table | ❌ Missing | No `withdrawals` migration |
| B2C payout endpoint | ❌ Missing | No controller method or route |
| Withdrawal UI (API or Filament) | ❌ Missing | |
| `MINIMUM_WITHDRAWAL_CREDITS` env | ⚠️ Config exists | In `config/system_settings.php:26` but ❌ not in `.env` |

### Escort Registration & Approval

| Item | Status | Notes |
|------|--------|-------|
| Admin creation via Filament | ✅ Done | `CreateEscort` creates User+Escort in transaction |
| `Escort` model (`verification_status`, `is_verified`) | ✅ Done | |
| `UserObserver` (welcome email) | ✅ Done | |
| **Public `POST /api/escort/register`** | ❌ **Missing** | No API endpoint for self-registration |
| **Approval queue in Filament** | ❌ **Missing** | No dedicated pending-applications view/filter |
| **Notification on approve/reject** | ❌ **Missing** | No notification sent to escort |
| **`EscortVerificationService`** | ❌ **Missing** | Verification logic is inline in Filament toggles |

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
| **Review API routes** | ✅ **Done** | 6 routes in `routes/api.php` (public + auth:sanctum) |
| **ReviewResource (Filament)** | ✅ Done | Admin moderation UI with verify/hide actions |
| **Report inappropriate review** | ✅ **Done** | `POST /api/reviews/{review}/report` creates Report row linked to the review |
| **ReviewService** | ✅ **Done** | Transactional CRUD + report logic in `app/Services/Review/ReviewService.php` |

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
| 2FA config/setup | ✅ **Done** | Secret generation, QR code URL, enable/confirm/disable via API + TOTP code verification |
| Recovery codes | ✅ **Done** | 8 codes generated on enable, consumed one-by-one, stored encrypted |

### CSS Fixes & Responsive Design

| Item | Status | Notes |
|------|--------|-------|
| Cross-browser/mobile audit | ❌ Not started | Applies to Next.js frontend primarily |

### Terms of Use & Privacy Policy

| Item | Status | Notes |
|------|--------|-------|
| Pages (Next.js frontend) | ❌ Deferred | Legal text must be provided by client per SOW §4 |

---

## Project Timeline (SOW §6)

| Phase | Features | Duration | Payment Milestone |
|-------|----------|----------|-------------------|
| **1 — Admin Portal** | Filament admin panel, Social Login | 2.5 wks | 40% (Mid-Project, with Phase 2) |
| **2 — Core Systems** | Credit/Commission, M-Pesa withdrawals, Escort registration & approval | 2 wks | 40% (Mid-Project, with Phase 1) |
| **3 — Monetization** | Paid messages/conversations, Earnings dashboard | 2 wks | 30% (Final Delivery) |
| **4 — UI & Polish** | CSS fixes, Reviews, Messaging improvements, 2FA, Terms & Policy | 1.5 wks | 30% (Final Delivery) |

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
