<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Admin user
        User::create([
            'name' => 'Admin User',
            'phone' => '+254712345678',
            'email' => 'admin@mjengo.test',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'role' => 'platform_admin',
            'kyc_status' => 'verified',
        ]);

        // Site owners
        User::create([
            'name' => 'John Karanja',
            'phone' => '+254701234567',
            'email' => 'john@constructionco.test',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'role' => 'site_owner',
            'kyc_status' => 'verified',
        ]);

        User::create([
            'name' => 'Mary Kipchoge',
            'phone' => '+254702234567',
            'email' => 'mary@buildit.test',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'role' => 'site_owner',
            'kyc_status' => 'verified',
        ]);

        User::create([
            'name' => 'Peter Mwangi',
            'phone' => '+254703234567',
            'email' => 'peter@newdev.test',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'role' => 'site_owner',
            'kyc_status' => 'pending',
        ]);

        // Workers and Foremen
        $workers = [
            ['name' => 'James Kipkemboi', 'phone' => '+254711001001'],
            ['name' => 'David Kipchoge', 'phone' => '+254711001002'],
            ['name' => 'Samuel Koech', 'phone' => '+254711001003'],
            ['name' => 'Michael Omondi', 'phone' => '+254711001004'],
            ['name' => 'Robert Mwangi', 'phone' => '+254711001005'],
            ['name' => 'Joseph Kipketer', 'phone' => '+254711001006'],
            ['name' => 'Thomas Kiplagat', 'phone' => '+254711001007'],
            ['name' => 'Charles Kipchoge', 'phone' => '+254711001008'],
            ['name' => 'Paul Macharia', 'phone' => '+254711001009'],
            ['name' => 'Daniel Kiplagat', 'phone' => '+254711001010'],
        ];

        foreach ($workers as $worker) {
            User::create([
                'name' => $worker['name'],
                'phone' => $worker['phone'],
                'email' => 'worker' . rand(1, 999) . '@mjengo.test',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'role' => 'worker',
                'kyc_status' => 'verified',
            ]);
        }

        $this->command->info('✓ Created 14 users (1 admin, 3 site owners, 10 workers)');
    }
}
