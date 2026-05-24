<?php

namespace App\Http\Controllers;

use App\Models\NjangiContribution;
use App\Models\NjangiSessionBeneficiary;

class NjangiContributionController extends Controller
{
    public function index()
    {
        $contributions = NjangiContribution::with([
                'cycle',
                'session',
                'contributor',
                'beneficiary',
                'paymentSubmission',
            ])
            ->orderByDesc('created_at')
            ->paginate(20);

        $totalContributions = NjangiContribution::count();
        $totalAmount = NjangiContribution::sum('amount');

        $memberBalances = NjangiContribution::with(['contributor', 'beneficiary'])
            ->get()
            ->groupBy('beneficiary_member_id')
            ->map(function ($items) {
                $first = $items->first();

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
            ->values();

        return view('njangi.contributions.index', compact(
            'contributions',
            'totalContributions',
            'totalAmount',
            'memberBalances'
        ));
    }
}