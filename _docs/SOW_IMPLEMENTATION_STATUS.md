# SOW Implementation Status

> Status assessment as of July 15, 2026 — mapping the RAHA-TELE codebase against the Statement of Work v1.0 (April 23, 2026).

## Phase Completion Summary

| Phase | Overall | Key Gaps |
|-------|---------|----------|
| **1 — Admin Portal** | ~65% | Missing Filament resources (Review, CreditTransaction, MpesaPayment, Conversation), analytics widgets, 2FA |
| **2 — Core Systems** | ~30% | Credit spending/commission broken, no B2C withdrawals, no public escort registration |
| **3 — Monetization** | ~10% | Data models exist, no logic enforces paid messages/conversations, no earnings dashboard |
| **4 — UI & Polish** | ~30% | Review system has model but no controller/routes/admin resource; typing done; reactions/attachments partial |

---

## Phase 1 — Admin Portal (2.5 wks)

### Filament 4 Admin Panel

| Item | Status | Notes |
|------|--------|-------|
| Panel setup + Shield | ✅ Done | `/admin-panel`, database notifications, Shield integrated |
| `UserResource` | ✅ Done | CRUD with suspend/activate/reset actions, Export, date filter |
| `EscortResource` | ✅ Done | Full CRUD, User+Escort created in single transaction |
| `MemberResource` | ✅ Done | Read-only View page with wallet/social login infolist |
| `ReviewResource` | ❌ Missing | No Filament admin moderation interface |
| `CreditTransactionResource` | ❌ Missing | Ledger view for admins |
| `MpesaPaymentResource` | ❌ Missing | Payment records view |
| `ConversationResource` | ❌ Missing | Chat thread management |
| `EscortResourceResource` | ❌ Missing | Media/gallery management |
| Analytics widgets | ❌ Missing | `app/Filament/Admin/Widgets/` is empty |
| Exporters (User, Member, Escort) | ✅ Done | 3 exporters exist |
| `HasDateRangeFilter` trait | ✅ Done | Applied to User/Escort tables |

### Social Login (Google & Facebook OAuth)

| Item | Status | Notes |
|------|--------|-------|
| Socialite installed | ✅ Done | `"laravel/socialite": "^5.28"` |
| `SocialAuthController` | ✅ Done | `redirect()` + `callback()` for Google/Facebook |
| Routes (`/api/auth/{provider}/...`) | ✅ Done | Member creation + 20 welcome credits + Sanctum token |
| Config (`config/services.php`) | ✅ Done | Google/Facebook keys + `socialite.redirect_frontend` |

### Two-Factor Authentication (2FA)

| Item | Status | Notes |
|------|--------|-------|
| TOTP package installed | ❌ Not started | No `pragmarx/google2fa-laravel` or similar |
| 2FA config/setup | ❌ Not started | No settings in any config file |
| Recovery codes | ❌ Not started | |

---

## Phase 2 — Core Systems (2 wks)

### Credit & Commission System

| Item | Status | Notes |
|------|--------|-------|
| `Member` wallet (credits, totals, expiry) | ✅ Done | `hasSufficientCredits()`, `addCredits()`, `deductCredits()` |
| `CreditTransaction` model | ✅ Done | Immutable ledger, polymorphic reference, scopes |
| M-Pesa → credit purchase flow | ✅ Done | STK push → callback → `awardCredits()` (idempotent) |
| **Commission split (30/70)** | ❌ **Broken** | No `CommissionService`; `unlockPhone()` destroys credits |
| **`usage` CreditTransactions** | ❌ **Broken** | Only `purchase`/`welcome` types written; spend writes none |
| **Escort crediting on spend** | ❌ **Broken** | Escort `earnings`/`balance` never incremented |
| `CreditService` | ❌ Missing | No service layer for credit operations |
| `CommissionService` | ❌ Missing | No commission calculation |
| `PLATFORM_COMMISSION_PERCENT` env | ❌ Missing | Not in `.env` |
| `CREDIT_EXPIRY_DAYS` env | ❌ Missing | Not in `.env`; no expiry enforcement job |
| Credit expiry enforcement | ❌ Missing | `credits_expire_at` field exists but unused |

### M-Pesa Withdrawals (B2C Payouts)

| Item | Status | Notes |
|------|--------|-------|
| `generateCredential()` | ✅ Done | B2C security credential preparation |
| B2C config in `config/services.php` | ✅ Done | `b2c_shortcode`, `b2c_command_id`, etc. |
| Withdrawal request model/table | ❌ Missing | No `withdrawals` migration |
| B2C payout endpoint | ❌ Missing | No controller method or route |
| Withdrawal UI (API or Filament) | ❌ Missing | |
| `MINIMUM_WITHDRAWAL` env | ❌ Missing | Not in `.env` |

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
| **Credit enforcement on send** | ❌ **Missing** | `ChatController::sendMessage()` does not check or deduct credits |
| **Payment verification flow** | ❌ **Missing** | No logic to verify payment before revealing content |
| **Commission split on paid messages** | ❌ **Missing** | |

### Escort Earnings Dashboard

| Item | Status | Notes |
|------|--------|-------|
| `Escort` `earnings`/`balance` fields | ✅ Done | Data model exists |
| **Earnings API endpoint** | ❌ **Missing** | No route or controller |
| **Earnings Filament page/widget** | ❌ **Missing** | No Filament UI |
| **Transaction history view** | ❌ **Missing** | |

---

## Phase 4 — UI & Polish (1.5 wks)

### Reviews System

| Item | Status | Notes |
|------|--------|-------|
| `Review` model | ✅ Done | `rating`, `comment`, `is_verified`, `is_visible`, scopes |
| `Escort::updateRating()` | ✅ Done | Recalculates aggregate rating/review_count |
| **ReviewController (API)** | ❌ **Missing** | No endpoints for CRUD |
| **Review API routes** | ❌ **Missing** | Not in `routes/api.php` |
| **ReviewResource (Filament)** | ❌ **Missing** | No admin moderation UI |
| **Report inappropriate review** | ❌ **Missing** | |
| **ReviewService** | ❌ **Missing** | |

### Messaging Improvements

| Item | Status | Notes |
|------|--------|-------|
| Typing indicators (`UserTyping` event + endpoint) | ✅ Done | |
| Message reactions (model + helpers) | ✅ Partial | No API endpoint to add/remove reactions |
| File/image attachments (model + formatting) | ✅ Partial | No upload/storage logic in controller |

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
| **1 — Admin Portal** | Filament admin panel, Social Login, 2FA | 2.5 wks | 40% (Mid-Project, with Phase 2) |
| **2 — Core Systems** | Credit/Commission, M-Pesa withdrawals, Escort registration & approval | 2 wks | 40% (Mid-Project, with Phase 1) |
| **3 — Monetization** | Paid messages/conversations, Earnings dashboard | 2 wks | 30% (Final Delivery) |
| **4 — UI & Polish** | CSS fixes, Reviews, Messaging improvements, Terms & Policy | 1.5 wks | 30% (Final Delivery) |

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
