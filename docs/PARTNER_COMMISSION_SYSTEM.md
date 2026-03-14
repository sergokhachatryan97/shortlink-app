# Partner Commission Payout System

> **Full partner flow**: See [PARTNER_SYSTEM.md](./PARTNER_SYSTEM.md) for activation, referral links, registration attribution, and the complete end-to-end flow.

## Overview

The partner/affiliate commission system assigns 10% (configurable) of a user's earnings to their referrer (partner). Commissions are prepared for external crypto payout via Heleket or CoinRush, not added to internal balance.

**Source vs payout provider (important):**
- **Source provider** = where the referred user paid (Heleket or CoinRush).
- **Payout provider** = which system the platform uses to pay the partner (admin-controlled).
- These are independent: e.g. a CoinRush payment can be paid out via Heleket.
- Admin sets payout provider per partner; default is Heleket.

Payouts run **once per day** (end of day). All pending commissions are aggregated by partner/payout_provider/currency/network/wallet and paid in one batch per group. Only providers in `payout_providers_enabled` are processed (Heleket only for now; CoinRush when API is ready).

## Architecture

```
User (source) pays → Platform receives money
    ↓
PartnerCommissionService.recordCommission()
    ↓
PartnerCommissionPayout (status: pending)
    ↓
ProcessPartnerPayoutsCommand (scheduled daily at 23:55)
    ↓
Groups pending by partner_user_id | provider | currency | network | wallet_address
    ↓
SendPartnerPayoutJob (one per group)
    ↓
PartnerPayoutService.sendBatchPayout() → AggregatedPayoutRequest
    ↓
PartnerPayoutProviderResolver → HeleketPayoutProvider | CoinrushPayoutProvider
    ↓
External API (when integrated)
    ↓
On success: all records in batch marked paid (same provider_transaction_id)
On failure: all records in batch marked failed (error_message)
```

## Files Created/Modified

### Created

| File | Purpose |
|------|---------|
| `database/migrations/2026_03_10_000001_add_partner_id_to_users_table.php` | Adds `partner_id` (self-ref) to users |
| `database/migrations/2026_03_10_000002_create_partner_payout_settings_table.php` | Partner payout config (provider, wallet, threshold) |
| `database/migrations/2026_03_10_000003_create_partner_commission_payouts_table.php` | Commission records for payouts |
| `app/Models/PartnerPayoutSetting.php` | Model for payout settings |
| `app/Models/PartnerCommissionPayout.php` | Model for commission payouts |
| `app/Contracts/PartnerPayoutProviderInterface.php` | Provider contract |
| `app/Contracts/PartnerPayoutResult.php` | Result DTO |
| `app/Services/PartnerCommissionService.php` | Records commission on earning events |
| `app/Services/PartnerPayoutService.php` | Sends payouts via resolved provider |
| `app/Services/PartnerPayout/PartnerPayoutProviderResolver.php` | Resolves provider by key |
| `app/Services/PartnerPayout/HeleketPayoutProvider.php` | Heleket payout (TODO: API) |
| `app/Services/PartnerPayout/CoinrushPayoutProvider.php` | CoinRush payout (TODO: API) |
| `app/Jobs/SendPartnerPayoutJob.php` | Queued job to send a payout |
| `app/Console/Commands/ProcessPartnerPayoutsCommand.php` | Groups pending commissions and dispatches batch jobs |
| `app/DTOs/AggregatedPayoutRequest.php` | DTO for batched payout (amount, currency, wallet, etc.) |
| `config/partner.php` | Default payout provider, allowed providers, enabled providers |
| `database/migrations/2026_03_11_000001_add_payout_provider_and_source_provider.php` | users.payout_provider, partner_commission_payouts.source_provider |

### Modified

| File | Change |
|------|--------|
| `app/Models/User.php` | Added `partner_id`, `partner()`, `referredUsers()`, `partnerPayoutSettings()`, `partnerCommissionsAsReceiver()`, `activePartnerPayoutSetting()` |
| `app/Http/Controllers/HeleketWebhookController.php` | Calls `recordPartnerCommission` on balance top-up and shortlink payment |
| `app/Http/Controllers/CoinRushWebhookController.php` | Same for CoinRush |
| `app/Http/Controllers/AdminController.php` | `setUserPartner()`, `addUserBalance()` redirect with tab |
| `resources/views/admin/dashboard.blade.php` | Partner column, set-partner form |
| `routes/web.php` | `admin.users.set-partner` route |
| `routes/console.php` | `partner:process-payouts` daily at 23:55 |
| `config/services.php` | `heleket.payout_api_key`, `coinrush.payout_api_key` |
| `.env.example` | Payout API key placeholders |

## Flow

### 1. Commission creation

When a user pays via Heleket or CoinRush (balance top-up or direct shortlink payment):

1. Webhook credits user balance / generates links.
2. `recordPartnerCommission()` is called with: `userId`, `amount`, `sourceType`, `sourceId`, `sourceProvider` (heleket/coinrush).
3. `PartnerCommissionService` resolves **payout provider** from: partner's `payout_provider` (admin-set) or `config('partner.default_payout_provider')` (heleket).
4. Service checks:
   - User has a partner (`partner_id` set).
   - Partner has `is_partner = true`.
   - No self-referral.
   - No duplicate for same `source_type` + `source_id`.
   - Partner has active payout settings for the **resolved payout provider** with valid `wallet_address`.
5. A `PartnerCommissionPayout` record is created with `provider` = payout provider, `source_provider` = source provider, `status = pending`.

### 2. Daily payout processing

1. `php artisan partner:process-payouts` runs daily at 23:55 (scheduler).
2. Command groups all pending commissions by: `partner_user_id`, `provider` (payout provider), `currency`, `network`, `wallet_address`.
3. Batches whose `provider` is not in `payout_providers_enabled` are skipped (logged; remain pending). Old CoinRush records stay pending until CoinRush is enabled.
4. For each remaining group with total amount > 0, it dispatches `SendPartnerPayoutJob` with the commission IDs.
5. The job locks the rows, marks them as `processing`, and calls `PartnerPayoutService.sendBatchPayout()`.
6. The service builds an `AggregatedPayoutRequest` with the total amount and calls the provider once.
7. On success: all records in the batch are marked `paid` with the same `provider_transaction_id`.
8. On failure: all records in the batch are marked `failed` with `error_message`.

### 3. Batching

- One payout per partner/provider/currency/network/wallet combination per day.
- No minimum threshold; any positive amount is paid.
- `min_payout_amount` column exists in DB for backward compatibility but is no longer used.

## Configuration

```env
# Partner payout (config/partner.php)
PARTNER_DEFAULT_PAYOUT_PROVIDER=heleket
PARTNER_PAYOUT_PROVIDERS_ENABLED=heleket

# Heleket (payout)
HELEKET_PAYOUT_API_KEY=

# CoinRush (payout)
COINRUSH_PAYOUT_API_KEY=
```

## Admin

- **User list** tab:
  - Set `partner_id` per user (0 = clear). Partners receive commission when their referred users pay.
  - **Payout provider** (per partner): Heleket, CoinRush, or Default (uses `PARTNER_DEFAULT_PAYOUT_PROVIDER`).
  - Add/Edit Partner Payout Setting: User ID (partner), Payout provider, Wallet address.
- **Partner payouts** tab: View all partner commission payout records (pending, processing, paid, failed).

## Old records

- **provider** = payout provider (outgoing). **source_provider** = where the payment came from.
- Old records without `source_provider` are fine; `provider` is used for payout.
- Old records with `provider=coinrush` are skipped until `PARTNER_PAYOUT_PROVIDERS_ENABLED` includes `coinrush`.

## TODOs

1. **Heleket payout API** – `HeleketPayoutProvider` has a placeholder. Integrate with Heleket withdrawal/payout endpoint when docs are available.
2. **CoinRush payout API** – Same for `CoinrushPayoutProvider`. When ready, add `coinrush` to `PARTNER_PAYOUT_PROVIDERS_ENABLED`; no code changes needed.
3. **Timezone** – Daily run uses `app.timezone`. Set `APP_TIMEZONE` in production for correct end-of-day.

## Adding a new provider

1. Create a class implementing `PartnerPayoutProviderInterface`.
2. Register it in `PartnerPayoutProviderResolver::__construct()`.
3. Add config in `config/services.php` and `.env.example`.
