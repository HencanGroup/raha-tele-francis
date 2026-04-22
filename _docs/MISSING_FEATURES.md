# Raha Tele Francis - Missing Features & Issues

## Overview

This document outlines the missing features, broken routes, and issues that need to be addressed to make the project production-ready.

---

## Table of Contents

1. [Critical Issues](#critical-issues)
2. [Credit System Issues](#credit-system-issues)
3. [Missing Features](#missing-features)
4. [Admin Portal Gaps](#admin-portal-gaps)
5. [Social Login Status](#social-login-status)
6. [Potential 404 Routes](#potential-404-routes)
7. [Priority Implementation Plan](#priority-implementation-plan)

---

## Critical Issues

### 1. Credit System - Incomplete Monetization Flow

**Problem:** When a client spends credits (e.g., unlocking a phone number), the credits are deducted from the client but never credited to anyone.

**Current Flow (Broken):**
```
Client spends 10 credits
    ↓
credits deducted from client (✓)
    ↓
CREDITS DISAPPEAR (✗ - Never credited to escort or platform)
```

**Needed Flow:**
```
Client spends 10 credits
    ↓
credits deducted from client
    ↓
Platform takes commission (e.g., 30% = 3 credits)
    ↓
Escort receives earnings (e.g., 70% = 7 credits)
    ↓
Recorded in credit_transactions table
```

**Files Affected:**
- `app/Http/Controllers/ApiController.php` (unlockPhone method)
- `app/Models/User.php` (deductCredits method)
- `app/Services/CreditService.php` (needs to be created)

---

## Credit System Issues

| Issue | Status | Description |
|-------|--------|-------------|
| Commission Calculation | ❌ Missing | No code calculates platform commission |
| Escort Earnings Credit | ❌ Missing | Escorts don't receive credits when clients spend |
| Credit Payout/Withdrawal | ❌ Missing | No way for escorts to withdraw earnings via M-Pesa |
| Credit Expiry | ⚠️ Partial | Field exists (`credits_expire_at`) but not enforced |
| Paid Messages | ⚠️ Partial | DB fields exist but feature not implemented |
| Paid Conversations | ⚠️ Partial | Fields exist but feature not implemented |

### Configuration Variables Needed

Add to `.env`:
```env
# Credit System
PLATFORM_COMMISSION_PERCENT=30
CREDIT_EXPIRY_DAYS=90
MINIMUM_WITHDRAWAL=100
```

---

## Missing Features

### 1. Escort Registration Flow

**Current State:** No public way for users to register as escorts.

**Needed:**
- [ ] Frontend registration page:
  - `resources/js/Pages/Auth/EscortRegister.jsx`
- [ ] Backend controller method:
  - `app/Http/Controllers/EscortRegistrationController.php`
- [ ] Route:
  - `POST /escort/apply` or `POST /escort/register`
- [ ] Admin approval queue:
  - Admin can view pending applications
  - Admin can approve/reject escorts

### 2. Message/Lock Features

**Current State:** Database fields exist but no implementation.

**Needed:**
- [ ] Lock message content (requires credits to view)
- [ ] Set credit cost per message
- [ ] Payment verification flow

### 3. Review System

**Current State:** Reviews table exists, but:
- [ ] No submission form in UI
- [ ] No update mechanism for escort rating
- [ ] No report inappropriate review feature

### 4. User Features

| Feature | Status | Notes |
|---------|--------|-------|
| Profile editing | ⚠️ Partial | Only basic fields |
| Account deletion | ❌ Missing | No self-delete flow |
| Password change | ⚠️ Partial | Via settings only |
| Two-factor auth | ❌ Missing | Not implemented |

### 5. Escort Features

| Feature | Status | Notes |
|---------|--------|-------|
| Photo upload | ⚠️ Partial | Basic implementation |
| Service management | ❌ Missing | No edit form |
| Rate customization | ❌ Missing | No edit form |
| Earnings dashboard | ❌ Missing | Basic stats only |
| Withdrawal request | ❌ Missing | No payout flow |

---

## Admin Portal Gaps

**Current State:** Very basic dashboard with just stats cards.

### Needed Pages:

| Page | Priority | Description |
|------|----------|-------------|
| User Management | High | CRUD: create, edit, suspend, ban users |
| Escort Verification Queue | High | Approve/reject escort applications |
| Content Moderation | Medium | Handle reported content |
| Reports Dashboard | Medium | Platform reports |
| Platform Analytics | Medium | Earnings, commissions, growth |
| Role Management | Medium | Assign/revoke roles |
| System Settings | Low | Configure app variables |

### Missing Routes:

```php
// User Management
Route::delete('/admin/users/{user}', [UserController::class, 'destroy']);
Route::patch('/admin/users/{user}/suspend', [UserController::class, 'suspend']);
Route::patch('/admin/users/{user}/ban', [UserController::class, 'ban']);

// Escort Management
Route::get('/admin/escorts/pending', [EscortController::class, 'pending']);
Route::patch('/admin/escorts/{escort}/approve', [EscortController::class, 'approve']);
Route::patch('/admin/escorts/{escort}/reject', [EscortController::class, 'reject']);
Route::patch('/admin/escorts/{escort}/feature', [EscortController::class, 'feature']);

// Analytics
Route::get('/admin/analytics', [AnalyticsController::class, 'index']);
```

---

## Social Login Status

**Current State:** NOT IMPLEMENTED

The application does NOT have social login functionality configured.

### Missing Packages:
```json
// composer.json - add
"laravel/socialite": "^5.0",
"socialiteproviders/google": "^4.0",
"socialiteproviders/facebook": "^4.0",
```

### Needed Configuration:

1. **Install packages:**
   ```bash
   composer require laravel/socialite socialiteproviders/google socialiteproviders/facebook
   ```

2. **Add to `config/services.php`:**
   ```php
   'google' => [
       'client_id' => env('GOOGLE_CLIENT_ID'),
       'client_secret' => env('GOOGLE_CLIENT_SECRET'),
       'redirect' => env('GOOGLE_REDIRECT_URI'),
   ],

   'facebook' => [
       'client_id' => env('FACEBOOK_CLIENT_ID'),
       'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
       'redirect' => env('FACEBOOK_REDIRECT_URI'),
   ],
   ```

3. **Add routes in `routes/auth.php`:**
   ```php
   Route::get('/auth/redirect/{provider}', [SocialAuthController::class, 'redirect']);
   Route::get('/auth/callback/{provider}', [SocialAuthController::class, 'callback']);
   ```

4. **Create controller:**
   - `app/Http/Controllers/Auth/SocialAuthController.php`

5. **Add buttons to login/register pages:**
   - `resources/js/Pages/Auth/Login.jsx`
   - `resources/js/Pages/Auth/Register.jsx`

---

## Potential 404 Routes

Based on route analysis, the following routes exist but may need verification:

### Defined Routes (Should Work):

| Route | Controller | Status |
|-------|------------|--------|
| `/` | Closure | ✅ OK |
| `/login` | AuthController | ✅ OK |
| `/register` | AuthController | ✅ OK |
| `/forgot-password` | PasswordResetLinkController | ✅ OK |
| `/reset-password/{token}` | NewPasswordController | ✅ OK |
| `/dashboard` | DashboardController | ✅ OK |
| `/chat` | ChatController | ✅ OK |
| `/chat/{conversation}` | ChatController | ✅ OK |
| `/escorts` | ApiController | ✅ OK |
| `/escort/{escort}` | EscortController | ✅ OK |

### Auth Routes (Need Signed URLs):

| Route | Controller | Notes |
|-------|------------|-------|
| `/verify-email/{id}/{hash}` | VerifyEmailController | Requires signed URL |
| `/email/verification-notification` | EmailVerificationNotificationController | Rate limited |

### API Routes:

| Route | Controller | Status |
|-------|------------|--------|
| `/api/data/counties` | ApiController | ✅ OK |
| `/api/data/towns` | ApiController | ✅ OK |
| `/api/payments/callback` | MpesaController | ✅ OK |

### Routes NOT in codebase that might be referenced:

| Expected Route | Notes |
|---------------|-------|
| `/user-management/profile` | No profile edit route found |
| `/escort/create` | No creation form route |
| `/admin/*` | No admin-specific routes |
| `/api/auth/*` | No auth API routes |

---

## Priority Implementation Plan

### Phase 1: Critical (Must Fix)

1. **Fix Credit System**
   - [ ] Create `CreditService` to handle commission calculation
   - [ ] Update `deductCredits` to credit escrow properly
   - [ ] Add credit transaction types: `usage` with commission

2. **Add Escort Registration**
   - [ ] Create registration form
   - [ ] Create approval workflow
   - [ ] Add admin approval queue

3. **Basic Admin Portal**
   - [ ] User management CRUD
   - [ ] Escort approval queue
   - [ ] Basic stats dashboard

### Phase 2: Important

4. **Paid Features**
   - [ ] Locked messages
   - [ ] Paid conversations
   - [ ] Phone unlock completion

5. **Escort Dashboard**
   - [ ] Earnings view
   - [ ] Withdrawal request
   - [ ] Profile management

### Phase 3: Polish

6. **Social Login**
   - [ ] Add Laravel Socialite
   - [ ] Google OAuth
   - [ ] Facebook OAuth

7. **Messaging Improvements**
   - [ ] File attachments
   - [ ] Typing indicators
   - [ ] Reactions

8. **Reviews System**
   - [ ] Submission form
   - [ ] Rating updates
   - [ ] Report feature

---

## Environment Variables to Add

```env
# ===========================================
# CREDIT SYSTEM
# ===========================================
PLATFORM_COMMISSION_PERCENT=30
MINIMUM_WITHDRAWAL=100
CREDIT_EXPIRY_DAYS=90
MESSAGE_COST=1
CONVERSATION_COST=50

# ===========================================
# SOCIAL LOGIN (if implementing)
# ===========================================
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=http://localhost:8000/auth/callback/google

FACEBOOK_CLIENT_ID=
FACEBOOK_CLIENT_SECRET=
FACEBOOK_REDIRECT_URI=http://localhost:8000/auth/callback/facebook

# ===========================================
# ADMIN
# ===========================================
ADMIN_EMAIL=admin@rahatele.com
```

---

## Files to Create

### New Controllers:
- `app/Http/Controllers/EscortRegistrationController.php`
- `app/Http/Controllers/Admin/UserController.php`
- `app/Http/Controllers/Admin/EscortController.php`
- `app/Http/Controllers/Admin/AnalyticsController.php`
- `app/Http/Controllers/Auth/SocialAuthController.php` (if social login)

### New Services:
- `app/Services/CreditService.php`

### New Middleware:
- `app/Http/Middleware/RoleMiddleware.php` (optional)

### New Blade/Pages:
- `resources/js/Pages/Auth/EscortRegister.jsx`
- `resources/js/Pages/Backend/Users/Index.jsx`
- `resources/js/Pages/Backend/Users/Edit.jsx`
- `resources/js/Pages/Backend/Escorts/Pending.jsx`
- `resources/js/Pages/Backend/Settings/Index.jsx`

---

*Last Updated: 2026-04-22*