<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Order matters:
     *  1. SettingsSeeder — default app settings (name, beneficiary count, etc.)
     *  2. MemberSeeder   — organization, ranks, roles, and member profiles
     *
     * NOTE: No admin user is seeded. The administrator account is created
     *       during the web-based installation wizard (step 4 — Admin User).
     * NOTE: RealDataSeeder (njangi cycles, sessions, sample data) is NOT
     *       called here — run it manually if demo data is needed.
     */
    public function run(): void
    {
        $this->call(SettingsSeeder::class);
        $this->call(MemberSeeder::class);
    }
}