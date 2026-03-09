# Payment System Documentation

This document describes how payment works in the Shortlink application: when it is required, which providers are supported, and how the flows behave end-to-end.

---

## Overview

There are two main payment flows:

1. **Link generation payment** – Pay to generate short links when free trial is exhausted
2. **Balance top-up** – Add funds to the user’s balance (logged-in users only)

Both flows can use **Heleket** (crypto) and **Tron/CoinRush** (USDT) as payment providers.

---

## 1. When Is Payment Required?

### Link Generation

- Each device (fingerprint or IP) gets **50 free links**.
- After that, generating links requires payment:
  - **Logged-in users with balance** → Amount is deducted from balance; no external payment.
  - **Logged-in users with subscription** → Links from plan are used; if at limit, charge from balance or redirect to payment.
  - **Otherwise** → User is sent to the payment page (Heleket or Tron).

### Amount Calculation

- `price_per_link` from `ShortlinkSetting` (default `0.01`).
- `min_amount` from `ShortlinkSetting` (default `0.10`).
- If free trial is exhausted: `amount = max(min_amount, count × price_per_link)`.
- If over limit but not exhausted: only charged for links beyond the free limit.

---

## 2. Payment Providers

### Heleket (Crypto)

- API: `config/services.heleket.base` (default `https://api.heleket.com`)
- **Config:** `HELEKET_MERCHANT`, `HELEKET_PAYMENT_KEY`, optionally `HELEKET_API_BASE`

**Flow:**

1. App creates a payment via Heleket API.
2. User is redirected to Heleket’s payment page.
3. After payment, Heleket redirects to our success URL and may call our webhook.
4. App confirms payment and performs the action (generate links or credit balance).

### Tron (CoinRush / USDT)

- Widget: `public/js/tron-payment.js` (TronPayment)
- API: `config/services.coinrush.api_url` (default `https://coinrush.link/store`)
- **Config:** `COINRUSH_STORE_KEY`, optionally `COINRUSH_API_URL`

**Flow:**

1. App prepares a transaction and returns `order_id` + `amount`.
2. TronPayment widget opens a modal with QR code, amount, and wallet address.
3. User pays with USDT on the Tron network.
4. CoinRush polls for payment completion and fires `onSuccess`.
5. User is redirected to our success URL.

---

## 3. Link Generation Payment Flow

### Step-by-Step

```
User fills form (URL + count)
        ↓
POST /generate
        ↓
Free trial remaining? ─Yes→ Generate links (free)
        ↓ No
Logged-in + balance OK? ─Yes→ Deduct balance, generate links
        ↓ No
Session: shortlink_pending (url, count, identifier)
        ↓
Redirect to /payment
        ↓
User chooses: Heleket or Tron
```

### Heleket Path

1. **POST** `shortlink.payment.initiate`
2. App creates `ShortlinkTransaction` (status: `pending`).
3. App calls Heleket API to create payment.
4. User is redirected to Heleket payment URL.
5. After payment, Heleket redirects to `/payment/success?order_id=...`.
6. **Webhook (source of truth):** Heleket calls our webhook; we verify signature, mark paid, generate links and store in `result_links`. User lands on success URL; handler reads tx and if paid, puts links in session (UI-only).

### Tron Path

1. **POST** `shortlink.payment-tron-prepare` → returns `order_id`, `amount`.
2. App creates `ShortlinkTransaction` with `provider_ref='tron'`.
3. Frontend opens TronPayment widget with `order_id` and `amount`.
4. User pays; widget’s `onSuccess` redirects to `/payment/tron/success?order_id=...&transaction_id=...`.
5. **Webhook** marks paid and generates links. Success handler is UI-only: reads tx, puts links in session if paid.

### Webhooks

Both Heleket and CoinRush can notify our backend:

- **Heleket:** `POST /api/webhooks/payments/heleket`
- **CoinRush:** `POST /api/webhooks/payments/coinrush`

Webhooks are the single source of truth: they verify (Heleket), mark paid, credit balance or generate links. Success redirects never mutate state.

---

## 4. Balance Top-Up Flow

Requires authenticated user.

1. **POST** `balance.heleket.initiate` or `balance.topup.prepare` with `amount` (0.10–10000).
2. App creates `ShortlinkTransaction` with `identifier = user:{id}`, `provider_ref = heleket_topup`.
3. User is redirected to Heleket.
4. Success redirect: `/balance/heleket/success?order_id=...`.
5. `BalanceController::heleketTopupSuccess()` credits the user’s balance.

### Tron Top-Up

1. **POST** `balance.topup.prepare` with `amount` (0.10–10000).
2. App creates `ShortlinkTransaction` with `provider_ref = tron_topup`.
3. Frontend opens TronPayment widget.
4. On success, redirect to `/balance/tron/success?order_id=...`.
5. `BalanceController::tronTopupSuccess()` credits the user’s balance.

Crediting happens only in the webhook; success redirects are read-only.

---

## 5. Routes Reference

| Route | Method | Purpose |
|-------|--------|---------|
| `shortlink.payment` | GET | Payment selection page |
| `shortlink.payment.initiate` | POST | Start Heleket payment for links |
| `shortlink.payment-success` | GET | Heleket success redirect (links) |
| `shortlink.payment-tron-prepare` | POST | Prepare Tron payment for links |
| `shortlink.payment-tron-success` | GET | Tron success redirect (links) |
| `shortlink.payment-status` | GET | Poll payment status (for pending page) |
| `balance.heleket.initiate` | POST | Start Heleket balance top-up |
| `balance.heleket.success` | GET | Heleket success redirect (balance) |
| `balance.topup.prepare` | POST | Prepare Tron balance top-up |
| `balance.tron.success` | GET | Tron success redirect (balance) |

### API Webhooks

| URL | Method | Purpose |
|-----|--------|---------|
| `/api/webhooks/payments/heleket` | POST | Heleket payment notifications |
| `/api/webhooks/payments/coinrush` | POST | CoinRush/Tron payment notifications |

---

## 6. Configuration

### .env

```env
# Heleket
HELEKET_API_BASE=https://api.heleket.com
HELEKET_MERCHANT=your_merchant_id
HELEKET_PAYMENT_KEY=your_payment_key

# CoinRush (Tron)
COINRUSH_STORE_KEY=your_store_key
COINRUSH_API_URL=https://coinrush.link/store
```

### Admin Settings

- `price_per_link` – Cost per link (default `0.01`)
- `min_amount` – Minimum charge (default `0.10`)

---

## 7. Models and Data

### ShortlinkTransaction

| Field | Description |
|-------|-------------|
| `order_id` | Unique ID (e.g. `sl-*`, `bal-*`) |
| `amount` | USD amount |
| `currency` | Usually `USD` |
| `status` | `pending`, `paid`, `failed` |
| `identifier` | `user:{id}` or fingerprint |
| `count` | Number of links (0 for balance top-up) |
| `url` | Original URL (links only) |
| `provider_ref` | Provider type (e.g. `heleket`, `tron`, `heleket_topup`, `tron_topup`) |
| `result_links` | JSON array of generated links (set by webhook for link payments) |

---

## 8. Dev Test Routes (APP_ENV=local)

| Route | Purpose |
|-------|---------|
| `/_test/payment-success` | Simulate link payment success with fake links |
| `/_test/add-balance` | Add balance for testing (requires auth, optional `?amount=10`) |

---

## 9. Flow Diagrams

### Link payment (Heleket)

```
[Payment Page] → POST initiate → [Heleket API] → Redirect to Heleket
                                                      ↓
[User pays] → [Heleket webhook] → Verify sign, mark paid, generate links, store in DB
                                                      ↓
[Heleket redirect] → /payment/success → Read tx, if paid put links in session → Index with download
```

### Link payment (Tron)

```
[Payment Page] → POST prepare → Create tx → [TronPayment Widget]
                                                      ↓
[User pays USDT] → [CoinRush webhook] → Mark paid, generate links, store in DB
                                                      ↓
Widget onSuccess → Redirect /payment/tron/success → Read tx, if paid put links in session → Index
```

### Balance top-up

```
[Balance page] → Prepare/Initiate → Create tx (identifier=user:{id})
                                                      ↓
[Webhook] → Credit user.balance, mark tx paid
                                                      ↓
[Success redirect] → Read tx, show success or "updating shortly"
```
