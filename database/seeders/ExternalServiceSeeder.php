<?php

namespace Database\Seeders;

use App\Models\ExternalCategory;
use App\Models\ExternalService;
use Illuminate\Database\Seeder;

class ExternalServiceSeeder extends Seeder
{
    public function run(): void
    {
        $category = ExternalCategory::firstOrCreate(
            ['slug' => 'short-links'],
            ['name' => 'Short Links', 'is_active' => true]
        );

        ExternalService::firstOrCreate(
            ['id' => 1],
            [
                'category_id' => $category->id,
                'name' => 'Short Links - Trastly',
                'is_active' => true,
                'is_external_available' => true,
                'min_quantity' => 1,
                'max_quantity' => 1000,
                'rate' => 0.0100,
                'unit' => 'link',
                'requires_link' => true,
                'requires_quantity' => true,
                'requires_custom_comments' => false,
            ]
        );
    }
}
