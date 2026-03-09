# Features Implementation Guide

## What Was Added

### 1. User Registration (Google & Telegram)
- **Google:** Laravel Socialite – set `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET` in `.env`
- **Telegram:** Login Widget – create a bot via @BotFather, set `TELEGRAM_BOT_TOKEN` and `TELEGRAM_BOT_USERNAME`
- Routes: `/auth/google`, `/auth/google/callback`, `/auth/telegram`, `POST /auth/logout`

### 2. User Balance & Tron Top-up
- Balance stored in `users.balance`
- Top-up via Tron (same CoinRush widget)
- Page: `/balance` (auth required)
- Success: `/balance/tron/success`

### 3. Subscription Plans
- Plans: Starter (500 links), Pro (2000 links), Unlimited
- Page: `/subscription` (auth required)
- Run seeder: `php artisan db:seed --class=SubscriptionPlanSeeder`

### 4. Subscription Expiry Job
- `ExpireSubscriptionsJob` runs daily – marks expired subscriptions, deletes links
- Add to scheduler: `php artisan schedule:work` (dev) or cron in production

## Free Trial Logic (Current)
- **Guests:** 50 links free per device (fingerprint/IP), then pay per link via Tron
- **Logged-in users:** Same 50 free then pay (or use balance – see below)

## What Still Needs Wiring

### Balance for Link Generation
Update `ShortlinkController::generate()` to:
1. If user is logged in and has balance ≥ amount due → deduct balance, generate links
2. Otherwise → redirect to payment (current flow)

### Subscription for Link Generation
When user has active subscription:
1. Generate links via ShortenService
2. Store in `shortlink_links` with `user_subscription_id`
3. Serve links from DB (subscription page, download CSV)

### CSV Before Expiry
- Add notification (email/in-app) when subscription expires in 7 days
- Add “Download all links” button on subscription page

## Migrations

```bash
php artisan migrate
```

New tables: `subscription_plans`, `user_subscriptions`, `shortlink_links`  
Updated: `users` (google_id, telegram_id, avatar, balance)
