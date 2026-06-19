<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\NjangiSession;
use App\Models\NjangiPaymentSubmission;
use App\Support\MemberResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class MemberPortalController extends Controller
{
    public function storeSubmission(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $member = MemberResolver::fromUser($user);
        if (!$member) {
            return redirect()->back()->with('error', 'No member profile found associated with this user account.');
        }

        $validated = $request->validate([
            'njangi_session_id' => ['required', 'exists:njangi_sessions,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'is_attending' => ['required', 'boolean'],
            'screenshot' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'], // Max 5MB
            'member_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $session = NjangiSession::findOrFail($validated['njangi_session_id']);

        // Prevent duplicate pending or approved submissions for this session by the same member
        $existing = NjangiPaymentSubmission::where('member_id', $member->id)
            ->where('njangi_session_id', $session->id)
            ->whereIn('status', ['pending', 'approved'])
            ->exists();

        if ($existing) {
            return redirect()->back()->withInput()->with('error', 'You already have a pending or approved payment submission for this session.');
        }

        // Store screenshot
        $path = $request->file('screenshot')->store('screenshots', 'public');

        NjangiPaymentSubmission::create([
            'organization_id' => $member->organization_id,
            'member_id' => $member->id,
            'njangi_cycle_id' => $session->njangi_cycle_id,
            'njangi_session_id' => $session->id,
            'amount' => $validated['amount'],
            'is_attending' => $validated['is_attending'],
            'screenshot_path' => $path,
            'status' => 'pending',
            'submitted_at' => now(),
            'member_note' => $validated['member_note'],
        ]);

        return redirect()->route('member.njangi-payments')->with('success', 'Your payment submission has been uploaded successfully and is pending review by the treasurer.');
    }

    public function myPayments(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $member = MemberResolver::fromUser($user);
        if (!$member) {
            abort(403, 'Unauthorized action. User profile is not linked to a member record.');
        }

        // Fetch all active cycles that the member is actually enrolled in.
        $memberCycles = \App\Models\NjangiCycle::where('status', 'active')
            ->whereHas('cycleMembers', function ($q) use ($member) {
                $q->where('member_id', $member->id);
            })->get();

        $activeCycle = null;
        if ($memberCycles->isNotEmpty()) {
            $selectedCycleId = $request->input('cycle_id');
            if ($selectedCycleId) {
                $activeCycle = $memberCycles->firstWhere('id', $selectedCycleId);
            }
            if (!$activeCycle) {
                $activeCycle = $memberCycles->first();
            }
        }

        if (!$activeCycle) {
            $activeCycle = \App\Models\NjangiCycle::where('status', 'active')->first();
        }

        if (!$activeCycle) {
            $activeCycle = \App\Models\NjangiCycle::orderBy('id', 'desc')->first();
        }

        $cycleMember = null;
        $activeSession = null;
        $sessions = collect();

        if ($activeCycle && $member) {
            $cycleMember = \App\Models\NjangiCycleMember::where('njangi_cycle_id', $activeCycle->id)
                ->where('member_id', $member->id)
                ->first();

            // Open/scheduled sessions for dropdown
            $sessions = NjangiSession::where('njangi_cycle_id', $activeCycle->id)
                ->whereIn('status', ['open', 'scheduled'])
                ->orderBy('session_date')
                ->get();

            // Current active session
            $activeSession = NjangiSession::where('njangi_cycle_id', $activeCycle->id)
                ->where('status', 'open')
                ->first()
                ?? NjangiSession::where('njangi_cycle_id', $activeCycle->id)
                    ->where('status', 'scheduled')
                    ->orderBy('session_date')
                    ->first();
        }

        // Query member's submissions
        $query = NjangiPaymentSubmission::where('member_id', $member->id)
            ->with(['session', 'cycle', 'reviewer']);

        if ($activeCycle) {
            $query->where('njangi_cycle_id', $activeCycle->id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('amount', 'like', "%{$search}%")
                  ->orWhere('member_note', 'like', "%{$search}%")
                  ->orWhereHas('session', function ($sq) use ($search) {
                      $sq->where('title', 'like', "%{$search}%")
                        ->orWhere('session_number', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $perPage = (int) $request->input('per_page', 10);
        if (!in_array($perPage, [5, 10, 20, 30, 50])) {
            $perPage = 10;
        }

        $submissions = $query->orderBy('id', 'desc')->paginate($perPage);

        // Format sessionsData for dropdown select options in JavaScript
        $sessionsData = [];
        foreach ($sessions as $s) {
            $bNames = [];
            foreach ($s->beneficiaries as $b) {
                $bNames[] = $b->cycleMember->member->first_name . ' ' . $b->cycleMember->member->last_name;
            }
            $sessionsData[$s->id] = [
                'id' => $s->id,
                'title' => $s->title ?: "Session #{$s->session_number}",
                'date' => $s->session_date->format('Y-m-d'),
                'beneficiaries' => $bNames,
            ];
        }

        $totalPaid = 0;
        $totalPending = 0;
        $pendingCount = 0;
        $approvedCount = 0;
        $hasBenefited = false;
        $benefitOrder = null;

        if ($activeCycle && $member) {
            $totalPaid = NjangiPaymentSubmission::where('member_id', $member->id)
                ->where('njangi_cycle_id', $activeCycle->id)
                ->where('status', 'approved')
                ->sum('amount');

            $totalPending = NjangiPaymentSubmission::where('member_id', $member->id)
                ->where('njangi_cycle_id', $activeCycle->id)
                ->where('status', 'pending')
                ->sum('amount');

            $pendingCount = NjangiPaymentSubmission::where('member_id', $member->id)
                ->where('njangi_cycle_id', $activeCycle->id)
                ->where('status', 'pending')
                ->count();

            $approvedCount = NjangiPaymentSubmission::where('member_id', $member->id)
                ->where('njangi_cycle_id', $activeCycle->id)
                ->where('status', 'approved')
                ->count();

            if ($cycleMember) {
                $benefitOrder = $cycleMember->benefit_order;

                $hasBenefited = \App\Models\NjangiDisbursement::where('njangi_cycle_member_id', $cycleMember->id)
                    ->where('status', 'paid')
                    ->exists()
                    || \App\Models\NjangiSessionBeneficiary::where('njangi_cycle_member_id', $cycleMember->id)
                        ->whereHas('session', function ($q) {
                            $q->where('status', 'closed');
                        })->exists();
            }
        }

        return view('njangi.member_payments', compact(
            'member',
            'activeCycle',
            'cycleMember',
            'activeSession',
            'sessions',
            'sessionsData',
            'submissions',
            'memberCycles',
            'totalPaid',
            'totalPending',
            'pendingCount',
            'approvedCount',
            'hasBenefited',
            'benefitOrder'
        ));
    }

    public function report(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $member = MemberResolver::fromUser($user);
        if (!$member) {
            return redirect()->route('dashboard')->with('error', 'No member profile found associated with this user account.');
        }

        // Fetch all active cycles that the member is actually enrolled in.
        $memberCycles = \App\Models\NjangiCycle::where('status', 'active')
            ->whereHas('cycleMembers', function ($q) use ($member) {
                $q->where('member_id', $member->id);
            })->get();

        $activeCycle = null;
        if ($memberCycles->isNotEmpty()) {
            $selectedCycleId = $request->input('cycle_id');
            if ($selectedCycleId) {
                $activeCycle = $memberCycles->firstWhere('id', $selectedCycleId);
            }
            if (!$activeCycle) {
                $activeCycle = $memberCycles->first();
            }
        }

        if (!$activeCycle) {
            $activeCycle = \App\Models\NjangiCycle::where('status', 'active')->first();
        }

        if (!$activeCycle) {
            $activeCycle = \App\Models\NjangiCycle::orderBy('id', 'desc')->first();
        }

        $cycleMember = null;
        $contributionsMade = collect();
        $contributionsReceived = collect();
        $refundSummary = collect();

        if ($activeCycle) {
            $cycleMember = \App\Models\NjangiCycleMember::where('njangi_cycle_id', $activeCycle->id)
                ->where('member_id', $member->id)
                ->first();

            $contributionsMade = \App\Models\NjangiContribution::where('contributor_member_id', $member->id)
                ->where('njangi_cycle_id', $activeCycle->id)
                ->with(['beneficiary', 'session'])
                ->orderBy('created_at', 'desc')
                ->get();

            $contributionsReceived = \App\Models\NjangiContribution::where('beneficiary_member_id', $member->id)
                ->where('njangi_cycle_id', $activeCycle->id)
                ->with(['contributor', 'session'])
                ->orderBy('created_at', 'desc')
                ->get();

            // Calculate refund summary
            $cycleBeneficiaries = \App\Models\NjangiSessionBeneficiary::whereHas('session', function ($q) use ($activeCycle) {
                $q->where('njangi_cycle_id', $activeCycle->id);
            })
            ->with(['cycleMember.member', 'session'])
            ->get();

            $refundSummary = $cycleBeneficiaries->groupBy('njangi_cycle_member_id')->map(function ($beneficiarySessions) use ($member, $activeCycle) {
                $first = $beneficiarySessions->first();
                if (!$first || !$first->cycleMember || !$first->cycleMember->member) return null;

                $beneficiaryMember = $first->cycleMember->member;
                if ($beneficiaryMember->id === $member->id) return null;

                // What we paid to B when B benefited
                $amountRefunded = \App\Models\NjangiContribution::where('contributor_member_id', $member->id)
                    ->where('beneficiary_member_id', $beneficiaryMember->id)
                    ->where('njangi_cycle_id', $activeCycle->id)
                    ->sum('amount');

                // What B contributed to us when we benefited (our expected refund obligation to B)
                $expectedToRefund = \App\Models\NjangiContribution::where('contributor_member_id', $beneficiaryMember->id)
                    ->where('beneficiary_member_id', $member->id)
                    ->where('njangi_cycle_id', $activeCycle->id)
                    ->sum('amount');

                $outstanding = max(0, $expectedToRefund - $amountRefunded);

                // Determine dynamic status
                if ($expectedToRefund > 0) {
                    if ($outstanding <= 0) {
                        $status = 'Settled';
                    } elseif ($amountRefunded > 0) {
                        $status = 'Partial';
                    } else {
                        $status = 'Pending';
                    }
                } else {
                    $status = $amountRefunded > 0 ? 'Paid' : 'Not Paid';
                }

                return [
                    'beneficiary_name'   => $beneficiaryMember->first_name . ' ' . $beneficiaryMember->last_name,
                    'expected_to_refund' => $expectedToRefund,
                    'amount_refunded'    => $amountRefunded,
                    'outstanding'        => $outstanding,
                    'status'             => $status,
                ];
            })
            ->filter()
            ->values();
        }

        return view('dashboard.member-report', compact(
            'member',
            'activeCycle',
            'cycleMember',
            'memberCycles',
            'contributionsMade',
            'contributionsReceived',
            'refundSummary'
        ));
    }
}
