# Payment System Refactor – Issues and Architecture

## 1. Current Issues Found

### Critical (Security/Correctness)

| Issue | Location | Problem |
|-------|----------|---------|
| **Success redirects mutate payment state** | `ShortlinkController::paymentSuccess`, `paymentTronSuccess` | Both generate links and put in session without verifying payment via webhook. Trusting browser redirect as proof of payment. |
| **Balance credited in success redirects** | `BalanceController::heleketTopupSuccess`, `tronTopupSuccess` | Crediting balance on redirect. Can be spoofed; duplicates webhook logic. |
| **No Heleket webhook signature verification** | `HeleketWebhookController` | Webhooks are accepted without verifying `sign` parameter. Anyone can POST fake paid webhooks. |
| **Heleket link payments never finalized by webhook** | `HeleketWebhookController` | Webhook only marks tx paid; never generates links. Links are generated only on redirect (unsafe). |
| **Tron link payments marked paid on redirect** | `ShortlinkController::paymentTronSuccess` | Updates `status` to `paid` and `provider_ref` on redirect (line 334–338). Trusting client. |
| **Double-credit risk** | Both redirects + webhooks | `:credited` in provider_ref is fragile; webhook can overwrite it when running after redirect. |
| **Link generation on redirect is repeatable** | `paymentSuccess`, `paymentTronSuccess` | User can refresh success URL; each hit regenerates links (wastes API calls) and overwrites session. |

### Moderate

| Issue | Location | Problem |
|-------|----------|---------|
| **Heleket link transactions missing provider_ref** | `ShortlinkController::initiatePayment` | No `provider_ref` set; harder to distinguish from top-up in webhook. |
| **Session dependency for success** | Success handlers | Require `shortlink_pending` in session; user can lose session (new tab, expired). |
| **No logging of suspicious webhook activity** | Webhook controllers | Duplicate webhooks, invalid signatures, unknown order_ids not logged. |
| **Cache keys unused** | Webhooks | `shortlink_paid_{orderId}` cached but never read by success handlers. |
| **CoinRush webhook** | Unknown | No public docs for signature verification; webhook accepted without verification. |

### Minor

| Issue | Location | Problem |
|-------|----------|---------|
| **Dead code path** | `paymentTronSuccess` | Lines 318–325: if already paid with links in session, redirect. Rare; session often empty after webhook-first flow. |
| **Comment admits unsafe design** | `paymentSuccess` line 366 | "For now we trust the redirect" – explicitly unsafe. |

---

## 2. Clean Final Architecture

### Principles

1. **Webhook = single source of truth** for payment completion.
2. **Success redirects = UI only** – never mutate balance, transaction status, or generate links.
3. **Links stored in DB** when webhook finalizes; success page reads and displays.
4. **Idempotent webhooks** – safe to receive duplicates.
5. **Heleket signature verification** – reject unverified webhooks.

### Flow: Link Generation Payment

1. **Initiation** – Create `ShortlinkTransaction` (pending), redirect to provider.
2. **Webhook** – Verify signature (Heleket). Mark paid. Generate links via ShortenService. Store in `result_links`. Log.
3. **Success redirect** – Look up tx by `order_id`. If paid + `result_links`: put in session, redirect to index. If pending: show "Confirming payment..." with auto-refresh. Never generate.

### Flow: Balance Top-Up

1. **Initiation** – Create `ShortlinkTransaction` (pending), redirect/prepare.
2. **Webhook** – Verify signature (Heleket). Mark paid. Credit user balance (idempotent). Log.
3. **Success redirect** – Look up tx by `order_id`. If paid: show success. If pending: show "Your balance will update shortly." Never credit.

### Transaction Types (via provider_ref)

- `heleket` – Heleket link payment
- `tron` – Tron link payment
- `heleket_topup` – Heleket balance top-up
- `tron_topup` – Tron balance top-up

---

## 3. Implementation Summary

### Files Changed

| File | Changes |
|------|---------|
| `app/Http/Controllers/HeleketWebhookController.php` | Rewritten: signature verification, balance credit, link generation in webhook only |
| `app/Http/Controllers/CoinRushWebhookController.php` | Rewritten: balance credit, link generation in webhook only |
| `app/Http/Controllers/ShortlinkController.php` | Success handlers UI-only; added `paymentStatus` poll; added `provider_ref` for Heleket |
| `app/Http/Controllers/BalanceController.php` | Success handlers UI-only; unified `topupSuccessRedirect` |
| `app/Models/ShortlinkTransaction.php` | Added `result_links` cast, `isShortlinkPayment()`, `isBalanceTopup()` |
| `database/migrations/..._add_result_links...` | New: `result_links` JSON column |
| `resources/views/shortlink/payment-pending.blade.php` | New: pending page with auto-poll |
| `resources/views/balance/index.blade.php` | Added `session('info')` display |
| `routes/web.php` | Added `shortlink.payment-status` route |
| `resources/views/shortlink/payment.blade.php` | Simplified Tron onSuccess redirect |

### Logic Removed

- Balance crediting from `heleketTopupSuccess` and `tronTopupSuccess`
- Link generation from `paymentSuccess` and `paymentTronSuccess`
- Transaction status update from `paymentTronSuccess`
- Session dependency (`shortlink_pending`) for success handlers
- `:credited` hack in `provider_ref` for idempotency (now use `status === 'paid'`)
- Cache usage for `shortlink_paid_*` (unused)
- Duplicate completion logic between webhook and redirect

### Why Each Removal Was Safe

- **Redirect crediting** – Trusting redirect = security risk; webhook is authoritative
- **Redirect link generation** – Same; also repeatable on refresh
- **`:credited`** – Replaced by checking `status === 'paid'` before any mutation
- **Session dependency** – Order ID in URL is sufficient; more robust
- **Cache** – Never read by success handlers; dead code
