<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\NjangiCycle;
use App\Models\NjangiCycleMember;
use App\Models\NjangiSession;
use App\Models\Member;
use App\Models\Organization;

class NjangiTestSeeder extends Seeder
{
    public function run(): void
    {
        $org = Organization::first();
        if (!$org) {
            $org = Organization::create([
                'name' => 'NFUH DMV',
            ]);
        }

        // 1. Create Cycle
        $cycle = NjangiCycle::create([
            'organization_id' => $org->id,
            'name' => 'Third Cycle (2026)',
            'year' => 2026,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'status' => 'active',
            'notes' => 'Test cycle',
        ]);

        // 2. Get existing members
        $members = Member::all();
        if ($members->isEmpty()) {
            return;
        }

        // 3. Add them to cycle
        foreach ($members as $index => $member) {
            NjangiCycleMember::create([
                'njangi_cycle_id' => $cycle->id,
                'member_id' => $member->id,
                'benefit_order' => $index + 1,
                'subscription_amount' => 100.00,
                'is_active' => true,
            ]);
        }

        // 4. Create 12 sessions
        for ($i = 1; $i <= 12; $i++) {
            NjangiSession::create([
                'organization_id' => $org->id,
                'njangi_cycle_id' => $cycle->id,
                'session_number' => $i,
                'session_date' => now()->copy()->startOfMonth()->addMonths($i - 6)->toDateString(),
                'title' => date('F', mktime(0, 0, 0, (($i - 1) % 12) + 1, 10)) . ' Session',
                'status' => $i < 6 ? 'closed' : ($i == 6 ? 'open' : 'scheduled'),
            ]);
        }
    }
}