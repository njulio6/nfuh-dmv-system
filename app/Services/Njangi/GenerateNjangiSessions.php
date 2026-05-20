<?php

namespace App\Services\Njangi;

use App\Models\NjangiCycle;
use App\Models\NjangiSession;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class GenerateNjangiSessions
{
    public function execute(NjangiCycle $cycle): void
    {
        $cycle->load(['cycleMembers', 'sessions']);

        // 1. Must have cycle members
        if ($cycle->cycleMembers->isEmpty()) {
            throw new RuntimeException('Cannot generate sessions because this cycle has no members.');
        }

        // 2. All cycle members must have benefit order
        $membersWithoutOrder = $cycle->cycleMembers->filter(function ($cycleMember) {
            return is_null($cycleMember->benefit_order);
        });

        if ($membersWithoutOrder->isNotEmpty()) {
            throw new RuntimeException('Cannot generate sessions until all cycle members have a benefit order.');
        }

        // 3. Prevent duplicate generation
        if ($cycle->sessions->isNotEmpty()) {
            throw new RuntimeException('Sessions already exist for this cycle.');
        }

        $startDate = Carbon::parse($cycle->start_date)->startOfMonth();
        $endDate = Carbon::parse($cycle->end_date)->startOfMonth();

        if ($startDate->gt($endDate)) {
            throw new RuntimeException('Invalid cycle dates.');
        }

        DB::transaction(function () use ($cycle, $startDate, $endDate) {
            $sessionNumber = 1;
            $currentDate = $startDate->copy();

            while ($currentDate->lte($endDate)) {
                NjangiSession::create([
                    'organization_id' => $cycle->organization_id,
                    'njangi_cycle_id' => $cycle->id,
                    'session_number' => $sessionNumber,
                    'session_date' => $currentDate->copy(),
                    'title' => $currentDate->format('F') . ' Session',
                    'notes' => null,
                    'status' => 'scheduled',
                ]);

                $sessionNumber++;
                $currentDate->addMonth();
            }
        });
    }
}