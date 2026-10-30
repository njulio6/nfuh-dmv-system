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

        // 1. Prevent duplicate generation
        if ($cycle->sessions->isNotEmpty()) {
            throw new RuntimeException('Sessions already exist for this cycle.');
        }

        $startDate = Carbon::parse($cycle->start_date)->startOfMonth();
        $endDate = Carbon::parse($cycle->end_date)->startOfMonth();

        if ($startDate->gt($endDate)) {
            throw new RuntimeException('Invalid cycle dates.');
        }

        $settings = \App\Models\Setting::first();
        $ordinal = $settings?->session_schedule_ordinal ?? 'first';
        $weekday = $settings?->session_schedule_weekday ?? 'Saturday';

        DB::transaction(function () use ($cycle, $startDate, $endDate, $ordinal, $weekday) {
            $sessionNumber = 1;
            $currentDate = $startDate->copy();

            while ($currentDate->lte($endDate)) {
                try {
                    $sessionDate = Carbon::parse("{$ordinal} {$weekday} of " . $currentDate->format('F Y'))->startOfDay();
                } catch (\Throwable $e) {
                    $sessionDate = $currentDate->copy()->startOfMonth();
                }

                NjangiSession::create([
                    'organization_id' => $cycle->organization_id,
                    'njangi_cycle_id' => $cycle->id,
                    'session_number' => $sessionNumber,
                    'session_date' => $sessionDate,
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