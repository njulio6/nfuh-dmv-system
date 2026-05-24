<?php

namespace App\Http\Controllers;

use App\Models\NjangiPaymentSubmission;
use App\Services\Njangi\ApproveNjangiPaymentSubmission;
use RuntimeException;

class NjangiPaymentSubmissionController extends Controller
{
    public function index()
    {
        $submissions = NjangiPaymentSubmission::with([
                'member',
                'cycle',
                'session',
                'reviewer',
            ])
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderByDesc('submitted_at')
            ->paginate(15);

        return view('njangi.submissions.index', compact('submissions'));
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