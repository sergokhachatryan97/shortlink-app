<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Payout Provider
    |--------------------------------------------------------------------------
    |
    | Platform uses Heleket for partner payouts. TRON network only.
    |
    */
    'default_payout_provider' => 'heleket',

    /*
    |--------------------------------------------------------------------------
    | Allowed Payout Providers
    |--------------------------------------------------------------------------
    |
    | Only Heleket is supported for partner payouts.
    |
    */
    'allowed_payout_providers' => ['heleket'],

    /*
    |--------------------------------------------------------------------------
    | Payout Providers Enabled for Automatic Processing
    |--------------------------------------------------------------------------
    |
    | Heleket sends payouts to TRON-compatible wallets (TRC20, TRON).
    |
    */
    'payout_providers_enabled' => ['heleket'],

    /*
    |--------------------------------------------------------------------------
    | Allowed Payout Routes (provider => [currency, network])
    |--------------------------------------------------------------------------
    |
    | Only TRON network. USDT (TRC20) and TRX (TRON).
    | Wallet addresses must be TRON-compatible (start with T, 34 chars).
    |
    */
    'allowed_payout_routes' => [
        'heleket' => [
            ['currency' => 'USDT', 'network' => 'TRC20', 'label' => 'USDT (TRC20)'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Payout Route (currency, network) per provider
    |--------------------------------------------------------------------------
    */
    'default_payout_route' => [
        'heleket' => ['currency' => 'USDT', 'network' => 'TRC20'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Minimum Payout Amount (USD)
    |--------------------------------------------------------------------------
    |
    | Batches below this amount stay pending. Admin can override via Settings.
    |
    */
    'default_min_payout_amount' => 100,

];
