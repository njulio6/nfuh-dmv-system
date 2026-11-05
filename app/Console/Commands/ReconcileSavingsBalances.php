<?php

namespace App\Console\Commands;

use App\Services\SavingsBalanceReconciler;
use Illuminate\Console\Command;

/**
 * Proof/repair tool for the Nov 2026 post-demo requirement that savings statements reconcile
 * against known test figures. Run without --fix to audit; run with --fix to correct any
 * mismatched previous_balance/new_balance snapshot (e.g. after re-seeding demo data).
 */
class ReconcileSavingsBalances extends Command
{
    protected $signature = 'savings:reconcile-balances {--fix : Correct any mismatched running-balance snapshots instead of only reporting them}';

    protected $description = 'Audit (and optionally correct) every savings transaction\'s previous/new balance snapshot against the member\'s real running total';

    public function handle(SavingsBalanceReconciler $reconciler): int
    {
        if ($this->option('fix')) {
            $fixed = $reconciler->fix();
            $this->info("Corrected {$fixed} transaction balance snapshot(s).");
            return self::SUCCESS;
        }

        $mismatches = $reconciler->report();

        if ($mismatches->isEmpty()) {
            $this->info('All savings transaction balance snapshots reconcile correctly.');
            return self::SUCCESS;
        }

        $this->error("Found {$mismatches->count()} mismatched transaction balance snapshot(s):");
        $this->table(
            ['Member ID', 'Transaction ID', 'Stored Previous', 'Expected Previous', 'Stored New', 'Expected New'],
            $mismatches->map(fn ($m) => [
                $m['member_id'],
                $m['transaction_id'],
                $m['stored_previous'] ?? '—',
                $m['expected_previous'],
                $m['stored_new'] ?? '—',
                $m['expected_new'],
            ])
        );
        $this->newLine();
        $this->warn('Run `php artisan savings:reconcile-balances --fix` to correct these.');

        return self::FAILURE;
    }
}
