<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->command->newLine();
        $this->command->info('🌱 Seeding Mjengo Database...');
        $this->command->newLine();

        $this->call([
            UserSeeder::class,
            SiteSeeder::class,
            SiteWorkerSeeder::class,
            InventorySeeder::class,
        ]);

        $this->command->newLine();
        $this->command->info('✅ Database seeding completed successfully!');
        $this->command->newLine();
        $this->command->info('📊 Seed Data:');
        $this->command->info('   • 3 Site Owners (1 pending KYC)');
        $this->command->info('   • 10 Workers (distributed across sites)');
        $this->command->info('   • 4 Construction Sites (1 completed)');
        $this->command->info('   • 10 Site-Worker assignments');
        $this->command->info('   • Inventory categories and starter items');
        $this->command->newLine();
        $this->command->info('🔐 Login Credentials:');
        $this->command->info('   Phone: +254712345678 (Admin)');
        $this->command->info('   Phone: +254701234567 (Site Owner 1)');
        $this->command->info('   Phone: +254702234567 (Site Owner 2)');
        $this->command->info('   Phone: +254711001001 (Worker - Foreman)');
        $this->command->newLine();
    }
}
