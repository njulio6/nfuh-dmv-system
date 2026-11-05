<?php

namespace App\Services;

use App\Models\Member;
use App\Models\SavingsTransaction;
use Illuminate\Support\Collection;

/**
 * Recalculates the previous_balance / new_balance snapshot stored on every savings_transactions
 * row from the member's actual chronological history, and reports or corrects any row whose
 * stored snapshot does not match.
 *
 * Only APPROVED deposit/adjustment/withdrawal rows affect the running settled balance (this
 * matches Member::getSavingsBalanceAttribute(), which only sums approved rows). PENDING and
 * REJECTED rows are informational snapshots taken at the settled balance as it stood when that
 * row was created; they do not themselves change the running total.
 */
class SavingsBalanceReconciler
{
    /**
     * Compare stored previous_balance/new_balance against the real running total for every
     * member, without changing anything. Returns one entry per mismatched transaction.
     */
    public function report(): Collection
    {
        $mismatches = collect();

        Member::where('participates_in_savings', true)->orWhereHas('savingsTransactions')->get()
            ->unique('id')
            ->each(function (Member $member) use ($mismatches) {
                $mismatches->push(...$this->mismatchesForMember($member));
            });

        return $mismatches;
    }

    /**
     * Recalculate and overwrite previous_balance/new_balance for every member's transactions
     * so statements and exports reconcile. Returns the number of rows corrected.
     */
    public function fix(): int
    {
        $fixed = 0;

        Member::where('participates_in_savings', true)->orWhereHas('savingsTransactions')->get()
            ->unique('id')
            ->each(function (Member $member) use (&$fixed) {
                $fixed += $this->fixForMember($member);
            });

        return $fixed;
    }

    /**
     * @return array<int, array{member_id:int, transaction_id:int, expected_previous:float, expected_new:float, stored_previous:?float, stored_new:?float}>
     */
    protected function mismatchesForMember(Member $member): array
    {
        $mismatches = [];
        $running = 0.0;

        foreach ($this->orderedTransactions($member) as $txn) {
            [$expectedPrevious, $expectedNew, $running] = $this->expectedSnapshot($txn, $running);

            $storedPrevious = $txn->previous_balance === null ? null : round((float) $txn->previous_balance, 2);
            $storedNew = $txn->new_balance === null ? null : round((float) $txn->new_balance, 2);

            if ($storedPrevious !== $expectedPrevious || $storedNew !== $expectedNew) {
                $mismatches[] = [
                    'member_id' => $member->id,
                    'transaction_id' => $txn->id,
                    'expected_previous' => $expectedPrevious,
                    'expected_new' => $expectedNew,
                    'stored_previous' => $storedPrevious,
                    'stored_new' => $storedNew,
                ];
            }
        }

        return $mismatches;
    }

    protected function fixForMember(Member $member): int
    {
        $fixed = 0;
        $running = 0.0;

        foreach ($this->orderedTransactions($member) as $txn) {
            [$expectedPrevious, $expectedNew, $running] = $this->expectedSnapshot($txn, $running);

            $storedPrevious = $txn->previous_balance === null ? null : round((float) $txn->previous_balance, 2);
            $storedNew = $txn->new_balance === null ? null : round((float) $txn->new_balance, 2);

            if ($storedPrevious !== $expectedPrevious || $storedNew !== $expectedNew) {
                $txn->forceFill([
                    'previous_balance' => $expectedPrevious,
                    'new_balance' => $expectedNew,
                ])->save();
                $fixed++;
            }
        }

        return $fixed;
    }

    /**
     * @return array{0: float, 1: float, 2: float} [expectedPrevious, expectedNew, newRunningSettledBalance]
     */
    protected function expectedSnapshot(SavingsTransaction $txn, float $runningSettled): array
    {
        $amount = (float) $txn->amount;

        if ($txn->status === 'approved') {
            $previous = $runningSettled;
            $isCredit = in_array($txn->type, ['deposit', 'adjustment'], true);
            $new = $isCredit ? $previous + $amount : max(0, $previous - $amount);
            return [round($previous, 2), round($new, 2), $new];
        }

        // Pending / rejected rows are a snapshot of the settled balance at creation time; they
        // do not themselves move the running settled total forward.
        $previous = $runningSettled;
        if ($txn->status === 'rejected') {
            $new = $previous;
        } else {
            // pending: preview of what the balance would become once this withdrawal is paid out
            $isCredit = in_array($txn->type, ['deposit', 'adjustment'], true);
            $new = $isCredit ? $previous + $amount : max(0, $previous - $amount);
        }

        return [round($previous, 2), round($new, 2), $runningSettled];
    }

    protected function orderedTransactions(Member $member)
    {
        return SavingsTransaction::where('member_id', $member->id)
            ->orderBy('transaction_date')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
    }
}
