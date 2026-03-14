# Partner System – Full Logic Documentation

This document describes the complete partner/affiliate flow in Trastly (trastly.org): activation, referral links, registration attribution, commissions, and payouts.

---

## Table of Contents

1. [Overview](#overview)
2. [Data Model](#data-model)
3. [Partner Activation](#partner-activation)
4. [Referral Links](#referral-links)
5. [Registration Attribution](#registration-attribution)
6. [Commission Recording](#commission-recording)
7. [Payout Settings](#payout-settings)
8. [Partner Dashboard](#partner-dashboard)
9. [Routes Reference](#routes-reference)
10. [Guards & Validation](#guards--validation)

---

## Overview

| Concept | Description |
|---------|-------------|
| **Partner** | A user who has explicitly activated partner mode. Not all users are partners. |
| **Referral** | A new user who signed up via a partner's referral link. |
| **Referral code** | Unique 8-character uppercase alphanumeric code assigned to each partner. |
| **Referral link** | `https://trastly.org/r/{referral_code}` – brings visitors to registration with attribution. |
| **Commission** | A percentage of the referred user's payments (Heleket/CoinRush) that goes to the partner. |
| **Payout** | External crypto payment to the partner's wallet (Heleket or CoinRush). |

Flow summary:

```
User clicks "Become a Partner" → is_partner=true, referral_code assigned
    ↓
Partner shares link: https://trastly.org/r/ABC123XY
    ↓
Visitor opens link → referral stored in session + cookie (30 days) → redirect to /register
    ↓
Visitor registers (email, Google, or Telegram) → partner_id set on new user
    ↓
Referred user pays via Heleket/CoinRush → commission recorded for partner
    ↓
Partner configures payout settings (wallet) → commissions paid externally
```

---

## Data Model

### Users table (relevant fields)

| Field | Type | Description |
|-------|------|-------------|
| `partner_id` | `bigint` (nullable, FK → users.id) | Referrer's user ID. Set when user registers via referral link. |
| `is_partner` | `boolean` (default: false) | Whether the user has activated partner mode. |
| `referral_code` | `string` (nullable, unique) | Partner's unique referral code (e.g. `ABC123XY`). |

### Relationships (User model)

```
User
├── partner()         → BelongsTo User (who referred this user)
├── referredUsers()   → HasMany User (users referred by this user)
├── partnerPayoutSettings()       → HasMany PartnerPayoutSetting
├── partnerCommissionsAsReceiver()→ HasMany PartnerCommissionPayout (as partner)
└── partnerCommissionsAsSource()  → HasMany PartnerCommissionPayout (as source user)
```

### PartnerPayoutSetting

| Field | Description |
|-------|-------------|
| `provider` | `heleket` or `coinrush` |
| `wallet_address` | Crypto wallet for receiving payouts |
| `percent` | Commission percent (default 10%) |
| `min_payout_amount` | (Deprecated) Column exists but is no longer used; payouts run daily without threshold |
| `is_active` | Whether this setting is used for commissions |

### PartnerCommissionPayout

| Field | Description |
|-------|-------------|
| `source_user_id` | User who made the payment |
| `partner_user_id` | Partner who receives the commission |
| `provider` | heleket or coinrush |
| `source_amount` | Original payment amount |
| `commission_amount` | Calculated commission |
| `status` | pending | processing | paid | failed |

---

## Partner Activation

### Entry point

- **UI**: "Become a Partner" button (profile or dashboard).
- **Action**: `POST /partner/activate`.

### Flow

1. User must be authenticated.
2. `PartnerActivationService::activate($user)` is called.
3. If user is already a partner (`is_partner = true`), return success without changes.
4. Otherwise, in a DB transaction:
   - Set `is_partner = true`.
   - If `referral_code` is empty, generate one via `ReferralCodeGenerator`.
5. Redirect to partner dashboard with success message.

### Referral code generation

- **Service**: `App\Services\ReferralCodeGenerator`.
- **Length**: 8 characters.
- **Charset**: `ABCDEFGHJKLMNPQRSTUVWXYZ23456789` (no I, O, 0, 1).
- **Uniqueness**: Retries until a code not present in `users.referral_code` is found.
- **Randomness**: `random_int()` for each character.

### Files

| File | Role |
|------|------|
| `app/Services/PartnerActivationService.php` | Activation logic |
| `app/Services/ReferralCodeGenerator.php` | Unique code generation |
| `app/Http/Controllers/PartnerController.php` | `activate()` action |

---

## Referral Links

### Format

```
https://trastly.org/r/{referral_code}
```

Base URL comes from `config('app.url')`. Ensure `APP_URL` is set correctly in production.

### Route

- **URL**: `GET /r/{code}`
- **Name**: `referral.redirect`

### Flow

1. Normalize `code` to uppercase.
2. Find `User` where `referral_code = code` AND `is_partner = true`.
3. If not found → redirect to shortlink index with "Invalid referral link."
4. Store in session:
   - `referral_code` = partner's referral code
   - `referral_code_at` = current timestamp (for 30-day expiry check)
5. Set cookie `referral_code` for 30 days (fallback if session expires).
6. Redirect to `auth.register` with info message: "You were referred by a partner. Sign up to get started!"

### Why session + cookie?

- Session: primary storage for same browser.
- Cookie: backup if user switches devices/sessions before registering.
- Cookie is not always sent on first request from external sites; session is more reliable when the redirect lands on the same domain.

---

## Registration Attribution

### When attribution happens

Attribution runs during user creation in:

- `AuthController::register()` – email/password
- `AuthController::handleGoogleCallback()` – Google OAuth (new users only)
- `AuthController::telegram()` – Telegram login (new users only)

### Flow: `resolveReferralPartner(Request $request): ?int`

1. Read `referral_code` from session, then from cookie if missing.
2. If empty → return `null`.
3. **30-day expiry**: If `referral_code_at` exists and is older than 30 days → return `null`.
4. Find `User` where `referral_code = code` and `is_partner = true`.
5. Return partner's `id` or `null`.

### Flow: User creation

1. Call `$partnerId = $this->resolveReferralPartner($request)`.
2. Create/update user with `partner_id => $partnerId` when applicable.
3. If partner was attributed: `$this->clearReferralAttribution($request)` (remove session keys).

### Guards

- Only new users get `partner_id` (Google/Telegram: `isNewUser` check).
- Self-referral: prevented in `PartnerCommissionService`, not at registration (a user cannot refer themselves via link because they must not be logged in to register).
- Invalid/expired codes: `resolveReferralPartner` returns `null`.

---

## Commission Recording

### When commissions are created

When a user pays via **Heleket** or **CoinRush** (balance top-up or direct shortlink payment):

- `HeleketWebhookController` → `PartnerCommissionService::recordCommission(..., 'heleket')`
- `CoinRushWebhookController` → `PartnerCommissionService::recordCommission(..., 'coinrush')`

### Flow: `recordCommission()`

1. **Early exit** if `$sourceAmount <= 0`.
2. **Partner lookup**: `$sourceUser->partner` (via `partner_id`).
3. **Partner checks**:
   - Partner must exist.
   - `$partner->is_partner` must be true.
   - No self-referral (`$partner->id !== $sourceUser->id`).
4. **Duplicate check**: No existing `PartnerCommissionPayout` for same `source_user_id`, `source_type`, `source_id`.
5. **Payout settings**: Partner must have an active `PartnerPayoutSetting` for the provider with non-empty `wallet_address`.
6. **Calculation**: `commission_amount = source_amount * (percent / 100)` (default 10%).
7. **Minimum**: Skip if `commission_amount < 0.01`.
8. **Create** `PartnerCommissionPayout` with `status = pending`.

### Self-referral prevention

If `partner_id` equals the user's own ID (edge case), `PartnerCommissionService` logs a warning and skips commission.

---

## Payout Settings

### Configuration

Partners configure payouts in the partner dashboard:

- **Provider**: `heleket` or `coinrush`.
- **Wallet address**: Required to receive commissions.
- **Min payout amount**: Optional threshold before payout.
- **is_active**: Whether this provider is used.

### Endpoint

- `POST /partner/payout-settings` (authenticated, partner only).

### Logic

- If `wallet_address` is empty → delete the setting for that provider.
- Otherwise → `updateOrCreate` by `user_id` + `provider`.

### Commission eligibility

Commissions are only recorded if:

1. Partner has `is_partner = true`.
2. Partner has at least one active payout setting with valid `wallet_address`.
3. Provider matches the payment source (Heleket/CoinRush).

See `docs/PARTNER_COMMISSION_SYSTEM.md` for payout processing (hourly job, external APIs).

---

## Partner Dashboard

### Route

- `GET /partner` → `partner.dashboard`

### Access

- Requires authentication.
- Any logged-in user can view; non-partners see an activation form.

### Data passed to view

| Variable | Description |
|----------|-------------|
| `user` | Current user |
| `referralLink` | Full referral URL or `null` |
| `referralCode` | Raw referral code or `null` |
| `payoutSettings` | `partnerPayoutSettings` collection |
| `hasActivePayout` | Whether any active setting with wallet exists |
| `referredCount` | Count of `referredUsers` |

### UI elements (backend-supported)

- Become a Partner form (POST to `/partner/activate`).
- Referral link display + copy.
- Payout settings form (Heleket/CoinRush).
- Placeholder for partner stats (counts, commissions).

---

## Routes Reference

| Method | URI | Name | Controller | Description |
|--------|-----|------|------------|-------------|
| GET | `/r/{code}` | `referral.redirect` | PartnerController::referralRedirect | Store referral, redirect to register |
| POST | `/partner/activate` | `partner.activate` | PartnerController::activate | Activate partner mode |
| GET | `/partner` | `partner.dashboard` | PartnerController::dashboard | Partner dashboard |
| POST | `/partner/payout-settings` | `partner.payout-settings.update` | PartnerController::updatePayoutSettings | Update payout config |

All partner routes use `auth` middleware where applicable.

---

## Guards & Validation

| Guard | Where | Behavior |
|-------|-------|----------|
| Authenticated | Partner activation, dashboard, payout settings | Redirect to login if not authenticated |
| Partner-only | Payout settings update | Must have `is_partner = true` |
| Valid partner | Referral redirect | `referral_code` must exist and `is_partner = true` |
| 30-day expiry | Registration attribution | `referral_code_at` older than 30 days is ignored |
| No duplicate commissions | PartnerCommissionService | Same `source_type` + `source_id` not recorded twice |
| No self-referral | PartnerCommissionService | `partner_id === source_user_id` → skip |
| Payout settings | PartnerCommissionService | Must have active setting with `wallet_address` |

---

## File Index

### Created for Partner Flow

| File | Purpose |
|------|---------|
| `app/Services/ReferralCodeGenerator.php` | Unique referral code generation |
| `app/Services/PartnerActivationService.php` | Partner activation logic |
| `app/Http/Controllers/PartnerController.php` | Activation, dashboard, referral redirect, payout settings |
| `database/migrations/2026_03_10_000004_add_partner_fields_to_users_table.php` | `is_partner`, `referral_code` on users |
| `resources/views/partner/dashboard.blade.php` | Partner dashboard view |

### Modified

| File | Changes |
|------|---------|
| `app/Models/User.php` | `is_partner`, `referral_code`, relationships |
| `app/Http/Controllers/AuthController.php` | `resolveReferralPartner`, `clearReferralAttribution`, attribution in register/Google/Telegram |
| `app/Services/PartnerCommissionService.php` | `$partner->is_partner` check |
| `routes/web.php` | Partner and referral routes |

### Related (Commission/Payout)

| File | Purpose |
|------|---------|
| `app/Services/PartnerCommissionService.php` | Record commissions |
| `app/Models/PartnerPayoutSetting.php` | Payout config per provider |
| `app/Models/PartnerCommissionPayout.php` | Commission records |
| `docs/PARTNER_COMMISSION_SYSTEM.md` | Commission and payout processing details |

---

## Sequence Diagram (High Level)

```
┌──────┐     Become Partner      ┌─────────────────┐     Activate     ┌──────────────────┐
│ User │ ──────────────────────► │ PartnerController│ ───────────────► │ PartnerActivation│
└──────┘                         └─────────────────┘                  │ Service          │
                                                                       └──────────────────┘
                                                                                │
                                                                                ▼
                                                                       ┌──────────────────┐
                                                                       │ ReferralCodeGen   │
                                                                       │ (if no code)      │
                                                                       └──────────────────┘

┌──────────┐   GET /r/ABC123   ┌─────────────────┐   Session+Cookie   ┌────────────┐
│ Visitor  │ ─────────────────►│ PartnerController│ ─────────────────►│ /register   │
└──────────┘                   │ referralRedirect │                    └────────────┘
                               └─────────────────┘

┌──────────┐   POST register   ┌─────────────────┐   resolveReferral  ┌────────────┐
│ Visitor  │ ─────────────────►│ AuthController   │ ─────────────────►│ User       │
└──────────┘                   │                  │   (partner_id)     │ (partner)  │
                               └─────────────────┘                    └────────────┘

┌──────────┐   Pay (Heleket/   ┌─────────────────┐   recordCommission ┌──────────────────┐
│ Referred │    CoinRush)      │ Webhook          │ ─────────────────►│ PartnerCommission│
│ User     │ ─────────────────►│ Controller       │                    │ Service          │
└──────────┘                   └─────────────────┘                    └──────────────────┘
                                                                                │
                                                                                ▼
                                                                       ┌──────────────────┐
                                                                       │ PartnerCommission│
                                                                       │ Payout (pending) │
                                                                       └──────────────────┘
```
