<?php

namespace App\Services\Njangi;

use App\Models\NjangiContribution;
use App\Models\NjangiPaymentSubmission;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ApproveNjangiPaymentSubmission
{
    public function execute(NjangiPaymentSubmission $submission, int $reviewerUserId, ?string $reviewNote = null): void
    {
        $submission->load([
            'session.beneficiaries',
            'session.beneficiaries.cycleMember.member',
        ]);

        if ($submission->status === 'approved') {
            throw new RuntimeException('This submission has already been approved.');
        }

        if ($submission->status === 'rejected') {
            throw new RuntimeException('Rejected submissions cannot be approved.');
        }

        $beneficiaries = $submission->session->beneficiaries;

        if ($beneficiaries->isEmpty()) {
            throw new RuntimeException('Cannot approve submission because this session has no beneficiaries assigned.');
        }

        DB::transaction(function () use ($submission, $reviewerUserId, $reviewNote, $beneficiaries) {
            $submission->update([
                'status' => 'approved',
                'reviewed_by' => $reviewerUserId,
                'reviewed_at' => now(),
                'review_note' => $reviewNote,
            ]);

            foreach ($beneficiaries as $sessionBeneficiary) {
                $beneficiaryMemberId = $sessionBeneficiary->cycleMember->member_id;

                $alreadyExists = NjangiContribution::where('payment_submission_id', $submission->id)
                    ->where('contributor_member_id', $submission->member_id)
                    ->where('beneficiary_member_id', $beneficiaryMemberId)
                    ->exists();

                if ($alreadyExists) {
                    continue;
                }

                NjangiContribution::create([
                    'organization_id' => $submission->organization_id,
                    'njangi_cycle_id' => $submission->njangi_cycle_id,
                    'njangi_session_id' => $submission->njangi_session_id,
                    'contributor_member_id' => $submission->member_id,
                    'beneficiary_member_id' => $beneficiaryMemberId,
                    'payment_submission_id' => $submission->id,
                    'amount' => $submission->amount,
                    'payment_date' => now()->toDateString(),
                    'payment_method' => 'zelle',
                    'reference_number' => null,
                    'notes' => 'Auto-created from approved Njangi payment submission.',
                ]);
            }
        });
    }
}