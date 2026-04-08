<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\NjangiCycle;
use App\Models\NjangiCycleMember;
use App\Models\NjangiSession;
use App\Models\Member;

class NjangiTestSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Cycle
    $cycle = NjangiCycle::create([
        'name' => '2026 Njangi Cycle',
        'year' => 2026,
        'start_date' => now()->toDateString(),
        'end_date' => now()->addYear()->toDateString(),
        'status' => 'active',
        'notes' => 'Test cycle',
    ]);

        // 2. Get 10 existing members (adjust if needed)
        $members = Member::take(10)->get();

        // 3. Add them to cycle
        foreach ($members as $index => $member) {
            NjangiCycleMember::create([
                'njangi_cycle_id' => $cycle->id,
                'member_id' => $member->id,
                'benefit_order' => $index + 1,
                'monthly_contribution_amount' => 100,
                'is_active' => true,
                'joined_at' => now(),
            ]);
        }

        // 4. Create 12 sessions
        for ($i = 1; $i <= 12; $i++) {
            NjangiSession::create([
                'njangi_cycle_id' => $cycle->id,
                'session_number' => $i,
                'session_date' => now()->addMonths($i - 1),
                'title' => "Session {$i}",
                'status' => 'scheduled',
            ]);
        }
    }
}