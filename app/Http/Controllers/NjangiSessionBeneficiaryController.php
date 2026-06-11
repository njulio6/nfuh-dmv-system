<?php

namespace App\Http\Controllers;

use App\Models\NjangiSession;
use App\Models\NjangiSessionBeneficiary;
use App\Models\NjangiCycleMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NjangiSessionBeneficiaryController extends Controller
{
    public function edit(NjangiSession $njangiSession)
    {
        $njangiSession->load(['cycle.cycleMembers.member', 'beneficiaries']);

        $cycleMembers = $njangiSession->cycle->cycleMembers()
            ->with('member')
            ->get()
            ->sortBy(function ($cm) {
                return $cm->benefit_order ?? 999;
            });

        $currentBeneficiaryIds = $njangiSession->beneficiaries->pluck('njangi_cycle_member_id')->toArray();

        return view('njangi.sessions.beneficiaries', compact('njangiSession', 'cycleMembers', 'currentBeneficiaryIds'));
    }

    public function update(Request $request, NjangiSession $njangiSession)
    {
        $validated = $request->validate([
            'cycle_member_ids' => ['nullable', 'array'],
            'cycle_member_ids.*' => ['exists:njangi_cycle_members,id'],
        ]);

        $selectedIds = $validated['cycle_member_ids'] ?? [];

        $settings = \App\Models\Setting::first();
        $minBeneficiaries = $settings?->beneficiary_count ?? 4;
        $singleBenefitEnabled = $settings?->single_benefit_constraint ?? true;

        // Enforce business rule: minimum of N beneficiaries per session
        if (count($selectedIds) < $minBeneficiaries) {
            $word = $minBeneficiaries === 1 ? 'beneficiary' : 'beneficiaries';
            return redirect()
                ->back()
                ->withInput()
                ->with('error', "A minimum of {$minBeneficiaries} {$word} must be selected for this session.");
        }

        // Enforce business rule: single-benefit per cycle constraint
        if ($singleBenefitEnabled) {
            $alreadyBenefited = NjangiSessionBeneficiary::whereIn('njangi_cycle_member_id', $selectedIds)
                ->where('njangi_session_id', '!=', $njangiSession->id)
                ->whereHas('session', function ($q) use ($njangiSession) {
                    $q->where('njangi_cycle_id', $njangiSession->njangi_cycle_id);
                })
                ->with('cycleMember.member', 'session')
                ->get();

            if ($alreadyBenefited->isNotEmpty()) {
                $names = $alreadyBenefited->map(fn($b) => ($b->cycleMember->member->name ?? 'Member') . " (" . ($b->session->title ?? 'Session') . ")")->join(', ');
                return redirect()
                    ->back()
                    ->withInput()
                    ->with('error', "The following member(s) have already benefited in this cycle: {$names}.");
            }
        }

        DB::transaction(function () use ($njangiSession, $selectedIds) {
            // Delete existing
            $njangiSession->beneficiaries()->delete();

            // Create new
            $slot = 1;
            foreach ($selectedIds as $cmId) {
                $cm = NjangiCycleMember::find($cmId);
                
                NjangiSessionBeneficiary::create([
                    'organization_id' => $njangiSession->organization_id,
                    'njangi_session_id' => $njangiSession->id,
                    'njangi_cycle_member_id' => $cmId,
                    'beneficiary_slot' => $slot,
                    'benefit_order' => $cm ? $cm->benefit_order : $slot,
                    'notes' => null,
                ]);
                $slot++;
            }
        });

        return redirect()
            ->route('njangi-cycles.show', $njangiSession->njangi_cycle_id)
            ->with('success', 'Session beneficiaries updated successfully.');
    }
}
