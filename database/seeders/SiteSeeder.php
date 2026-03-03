<?php

namespace Database\Seeders;

use App\Models\Site;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SiteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get site owners
        $owner1 = User::where('phone', '+254701234567')->first();
        $owner2 = User::where('phone', '+254702234567')->first();

        // Site 1
        Site::create([
            'owner_id' => $owner1->id,
            'name' => 'Westlands Office Complex',
            'location' => 'Westlands, Nairobi',
            'is_completed' => false,
            'payout_method' => 'platform_managed',
            'payout_window_start' => 'Monday',
            'payout_window_end' => 'Friday',
            'payout_opens_at' => '09:00:00',
            'payout_closes_at' => '17:00:00',
        ]);

        // Site 2
        Site::create([
            'owner_id' => $owner1->id,
            'name' => 'Kilimani Residential Project',
            'location' => 'Kilimani, Nairobi',
            'is_completed' => false,
            'payout_method' => 'platform_managed',
            'payout_window_start' => 'Tuesday',
            'payout_window_end' => 'Saturday',
            'payout_opens_at' => '08:00:00',
            'payout_closes_at' => '18:00:00',
        ]);

        // Site 3
        Site::create([
            'owner_id' => $owner2->id,
            'name' => 'Langata Shopping Mall',
            'location' => 'Langata, Nairobi',
            'is_completed' => false,
            'payout_method' => 'owner_managed',
            'payout_window_start' => 'Wednesday',
            'payout_window_end' => 'Sunday',
            'payout_opens_at' => '10:00:00',
            'payout_closes_at' => '16:00:00',
        ]);

        // Site 4
        Site::create([
            'owner_id' => $owner2->id,
            'name' => 'Karen Estate Extension',
            'location' => 'Karen, Nairobi',
            'is_completed' => true,
            'payout_method' => 'platform_managed',
            'payout_window_start' => 'Monday',
            'payout_window_end' => 'Friday',
            'payout_opens_at' => '09:00:00',
            'payout_closes_at' => '17:00:00',
        ]);

        $this->command->info('✓ Created 4 construction sites');
    }
}
