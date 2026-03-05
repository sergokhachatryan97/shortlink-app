<?php

namespace Database\Seeders;

use App\Models\ShortlinkSetting;
use Illuminate\Database\Seeder;

class ShortlinkSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'price_per_link' => '0.01',
            'min_amount' => '0.10',
        ];
        foreach ($defaults as $key => $value) {
            ShortlinkSetting::firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
