<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    /**
     * Seed the application's inventory with empty data.
     * 
     * Categories and items are now managed per-site by owners.
     * Owners can apply industry templates when enabling inventory on their site.
     * This seeder no longer creates global inventory data.
     */
    public function run(): void
    {
        // Intentionally empty - inventory is now site-specific
        // Owners manage their own categories and items via InventoryTemplateService
    }
}
