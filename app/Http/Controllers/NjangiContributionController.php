<?php

namespace App\Http\Controllers;

use App\Models\NjangiContribution;
use App\Models\NjangiSessionBeneficiary;

class NjangiContributionController extends Controller
{
    public function index()
    {
        $cycleId = request('cycle_id');
        $activeCycle = null;
        if ($cycleId) {
            $activeCycle = \App\Models\NjangiCycle::find($cycleId);
        } else {
            $activeCycle = \App\Models\NjangiCycle::where('status', 'active')->first()
                ?? \App\Models\NjangiCycle::latest('id')->first();
        }

        $query = NjangiContribution::with([
                'cycle',
                'session',
                'contributor',
                'beneficiary',
                'paymentSubmission',
            ]);

        $totalContributionsQuery = NjangiContribution::query();
        $totalAmountQuery = NjangiContribution::query();
        $balancesQuery = NjangiContribution::with(['contributor', 'beneficiary', 'session']);

        if ($activeCycle) {
            $query->where('njangi_cycle_id', $activeCycle->id);
            $totalContributionsQuery->where('njangi_cycle_id', $activeCycle->id);
            $totalAmountQuery->where('njangi_cycle_id', $activeCycle->id);
            $balancesQuery->where('njangi_cycle_id', $activeCycle->id);
        }

        $contributions = $query->orderByDesc('created_at')->paginate(20);
        $totalContributions = $totalContributionsQuery->count();
        $totalAmount = $totalAmountQuery->sum('amount');

        $memberBalances = $balancesQuery->get()
            ->groupBy('beneficiary_member_id')
            ->map(function ($items) {
                $first = $items->first();
                if (!$first) return null;

                $sessionBeneficiaryCount = NjangiSessionBeneficiary::where(
                     'njangi_session_id',
                     $first->njangi_session_id
                 )->count();

                $received = $items->sum('amount');
                $expected = $sessionBeneficiaryCount * $first->amount;
                $remaining = $expected - $received;

                return [
                    'beneficiary' => $first->beneficiary->first_name . ' ' . $first->beneficiary->last_name,
                    'expected' => $expected,
                    'received' => $received,
                    'remaining' => $remaining,
                    'status' => $remaining <= 0 ? 'Fully Refunded' : ($received > 0 ? 'Partially Refunded' : 'Not Refunded'),
                ];
            })
            ->filter()
            ->values();

        return view('njangi.contributions.index', compact(
            'contributions',
            'totalContributions',
            'totalAmount',
            'memberBalances',
            'activeCycle'
        ));
    }
}