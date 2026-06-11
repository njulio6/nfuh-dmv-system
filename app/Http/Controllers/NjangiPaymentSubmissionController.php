<?php

namespace App\Http\Controllers;

use App\Models\NjangiPaymentSubmission;
use App\Services\Njangi\ApproveNjangiPaymentSubmission;
use RuntimeException;

class NjangiPaymentSubmissionController extends Controller
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

        $query = NjangiPaymentSubmission::with([
                'member',
                'cycle',
                'session',
                'reviewer',
            ]);

        if ($activeCycle) {
            $query->where('njangi_cycle_id', $activeCycle->id);
        }

        $submissions = $query->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderByDesc('submitted_at')
            ->paginate(15);

        return view('njangi.submissions.index', compact('submissions', 'activeCycle'));
    }

    public function approve(
        NjangiPaymentSubmission $submission,
        ApproveNjangiPaymentSubmission $service
    ) {
        try {
            $reviewerUserId = auth()->id() ?? 1;

            $service->execute($submission, $reviewerUserId);

            return redirect()
                ->back()
                ->with('success', 'Payment approved and contributions recorded.');
        } catch (RuntimeException $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }

    public function reject(NjangiPaymentSubmission $submission)
    {
        if ($submission->status !== 'pending') {
            return redirect()
                ->back()
                ->with('error', 'Only pending submissions can be rejected.');
        }

        $submission->update([
            'status' => 'rejected',
            'reviewed_by' => auth()->id() ?? 1,
            'reviewed_at' => now(),
        ]);

        return redirect()
            ->back()
            ->with('success', 'Payment submission rejected.');
    }
}