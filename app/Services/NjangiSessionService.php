<?php

namespace App\Services;

use App\Models\NjangiContribution;
use App\Models\NjangiSession;
use App\Models\NjangiSessionBeneficiary;
use Illuminate\Support\Facades\DB;

class NjangiSessionService
{
    public function openSession(NjangiSession $session, int $beneficiariesPerSession = 4): NjangiSession
    {
        return DB::transaction(function () use ($session, $beneficiariesPerSession) {
            $session->load('cycle.cycleMembers.member');

            $cycle = $session->cycle;

            $activeCycleMembers = $cycle->cycleMembers()
                ->where('is_active', true)
                ->orderBy('benefit_order')
                ->get();

            // 1. Mark session open
            $session->update([
                'status' => 'open',
            ]);

            // 2. Create contribution records for all active members
            foreach ($activeCycleMembers as $cycleMember) {
                NjangiContribution::firstOrCreate(
                    [
                        'njangi_session_id' => $session->id,
                        'njangi_cycle_member_id' => $cycleMember->id,
                    ],
                    [
                        'amount_due' => $cycleMember->monthly_contribution_amount,
                        'amount_paid' => 0,
                        'penalty_amount' => 0,
                        'other_adjustment' => 0,
                        'payment_status' => 'pending',
                        'notes' => 'Auto-generated when session opened',
                    ]
                );
            }

            // 3. Assign beneficiaries for this session
            $startOrder = (($session->session_number - 1) * $beneficiariesPerSession) + 1;
            $endOrder = $startOrder + $beneficiariesPerSession - 1;

            $beneficiaries = $cycle->cycleMembers()
                ->where('is_active', true)
                ->whereBetween('benefit_order', [$startOrder, $endOrder])
                ->orderBy('benefit_order')
                ->get();

            foreach ($beneficiaries as $index => $cycleMember) {
                NjangiSessionBeneficiary::firstOrCreate(
                    [
                        'njangi_session_id' => $session->id,
                        'njangi_cycle_member_id' => $cycleMember->id,
                    ],
                    [
                        'beneficiary_slot' => $index + 1,
                        'benefit_order' => $cycleMember->benefit_order,
                        'notes' => 'Auto-assigned when session opened',
                    ]
                );
            }

            return $session->fresh(['beneficiaries.cycleMember.member', 'contributions']);
        });
    }
}