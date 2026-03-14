<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Payout Provider
    |--------------------------------------------------------------------------
    |
    | When a partner has no admin-set payout_provider, this default is used.
    | Payout provider = which system sends money to the partner (heleket/coinrush).
    | This is separate from source provider (where the referred user paid).
    |
    */
    'default_payout_provider' => env('PARTNER_DEFAULT_PAYOUT_PROVIDER', 'heleket'),

    /*
    |--------------------------------------------------------------------------
    | Allowed Payout Providers
    |--------------------------------------------------------------------------
    |
    | Providers that admin may configure for partner payouts.
    |
    */
    'allowed_payout_providers' => ['heleket', 'coinrush'],

    /*
    |--------------------------------------------------------------------------
    | Payout Providers Enabled for Automatic Processing
    |--------------------------------------------------------------------------
    |
    | Only providers in this list are actually used for daily automatic payouts.
    | CoinRush payout API is not yet integrated; keep only 'heleket' until ready.
    | Pending commissions with disabled providers are skipped (remain pending).
    |
    */
    'payout_providers_enabled' => array_filter(
        explode(',', env('PARTNER_PAYOUT_PROVIDERS_ENABLED', 'heleket')),
        fn ($p) => !empty(trim($p))
    ) ?: ['heleket'],

];
