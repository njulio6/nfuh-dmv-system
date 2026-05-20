<?php

namespace App\Http\Controllers;

use App\Models\NjangiPaymentSubmission;
use App\Services\Njangi\ApproveNjangiPaymentSubmission;
use Illuminate\Http\Request;
use RuntimeException;

class NjangiPaymentSubmissionController extends Controller
{
    public function approve(
        NjangiPaymentSubmission $submission,
        ApproveNjangiPaymentSubmission $service
    ) {
        try {
            $service->execute($submission, auth()->id());

            return redirect()
                ->back()
                ->with('success', 'Payment approved and contributions recorded.');
        } catch (RuntimeException $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }
}