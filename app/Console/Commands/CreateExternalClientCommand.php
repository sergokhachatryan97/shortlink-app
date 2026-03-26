<?php

namespace App\Console\Commands;

use App\Models\ExternalClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class CreateExternalClientCommand extends Command
{
    protected $signature = 'external-client:create
        {name : Client display name}
        {--user_id= : Optional user id to attach}
        {--currency=USD : Client balance currency}
        {--balance=0 : Initial balance}
        {--rate-limit= : Optional rate limit per minute}';

    protected $description = 'Create external API client and print raw API key once';

    public function handle(): int
    {
        $rawKey = 'trst_' . Str::random(48);
        $hash = hash('sha256', $rawKey);

        $client = ExternalClient::create([
            'user_id' => $this->option('user_id') !== null ? (int) $this->option('user_id') : null,
            'name' => (string) $this->argument('name'),
            'api_key_hash' => $hash,
            'api_key_encrypted' => Crypt::encryptString($rawKey),
            'is_active' => true,
            'balance' => (float) $this->option('balance'),
            'currency' => (string) $this->option('currency'),
            'rate_limit_per_minute' => $this->option('rate-limit') !== null ? (int) $this->option('rate-limit') : null,
        ]);

        $this->info('External client created.');
        $this->line('Client ID: ' . $client->id);
        $this->line('Name: ' . $client->name);
        $this->warn('Raw API key (shown once): ' . $rawKey);
        $this->line('Store it securely. Only hash is stored.');

        return Command::SUCCESS;
    }
}
