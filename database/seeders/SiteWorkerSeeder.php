<?php

namespace Database\Seeders;

use App\Models\Site;
use App\Models\SiteWorker;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SiteWorkerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all workers
        $workers = User::where('role', 'worker')->get();
        $sites = Site::all();

        // Assign workers to sites
        // Site 1 - Westlands Office Complex
        $site1 = $sites->first();
        SiteWorker::create([
            'site_id' => $site1->id,
            'user_id' => $workers[0]->id, // James - Foreman
            'is_foreman' => true,
            'daily_rate' => 1500,
            'weekly_rate' => 7000,
            'started_at' => now()->subDays(60),
        ]);

        SiteWorker::create([
            'site_id' => $site1->id,
            'user_id' => $workers[1]->id, // David
            'is_foreman' => false,
            'daily_rate' => 800,
            'weekly_rate' => 4000,
            'started_at' => now()->subDays(45),
        ]);

        SiteWorker::create([
            'site_id' => $site1->id,
            'user_id' => $workers[2]->id, // Samuel
            'is_foreman' => false,
            'daily_rate' => 800,
            'weekly_rate' => 4000,
            'started_at' => now()->subDays(45),
        ]);

        // Site 2 - Kilimani Residential
        $site2 = $sites->get(1);
        SiteWorker::create([
            'site_id' => $site2->id,
            'user_id' => $workers[3]->id, // Michael - Foreman
            'is_foreman' => true,
            'daily_rate' => 1500,
            'weekly_rate' => 7000,
            'started_at' => now()->subDays(30),
        ]);

        SiteWorker::create([
            'site_id' => $site2->id,
            'user_id' => $workers[4]->id, // Robert
            'is_foreman' => false,
            'daily_rate' => 750,
            'weekly_rate' => 3750,
            'started_at' => now()->subDays(30),
        ]);

        SiteWorker::create([
            'site_id' => $site2->id,
            'user_id' => $workers[5]->id, // Joseph
            'is_foreman' => false,
            'daily_rate' => 750,
            'weekly_rate' => 3750,
            'started_at' => now()->subDays(20),
        ]);

        // Site 3 - Langata Shopping Mall
        $site3 = $sites->get(2);
        SiteWorker::create([
            'site_id' => $site3->id,
            'user_id' => $workers[6]->id, // Thomas - Foreman
            'is_foreman' => true,
            'daily_rate' => 1600,
            'weekly_rate' => 7500,
            'started_at' => now()->subDays(15),
        ]);

        SiteWorker::create([
            'site_id' => $site3->id,
            'user_id' => $workers[7]->id, // Charles
            'is_foreman' => false,
            'daily_rate' => 850,
            'weekly_rate' => 4250,
            'started_at' => now()->subDays(15),
        ]);

        SiteWorker::create([
            'site_id' => $site3->id,
            'user_id' => $workers[8]->id, // Paul
            'is_foreman' => false,
            'daily_rate' => 850,
            'weekly_rate' => 4250,
            'started_at' => now()->subDays(10),
        ]);

        // Site 4 - Karen Estate (completed)
        $site4 = $sites->get(3);
        SiteWorker::create([
            'site_id' => $site4->id,
            'user_id' => $workers[9]->id, // Daniel
            'is_foreman' => true,
            'daily_rate' => 1200,
            'weekly_rate' => 6000,
            'started_at' => now()->subDays(120),
            'ended_at' => now()->subDays(5), // Project completed 5 days ago
        ]);

        $this->command->info('✓ Assigned 10 workers to 4 sites with varying roles and rates');
    }
}
