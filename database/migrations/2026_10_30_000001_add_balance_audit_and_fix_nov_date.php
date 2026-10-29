<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('savings_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('savings_transactions', 'previous_balance')) {
                $table->decimal('previous_balance', 12, 2)->nullable()->after('amount');
            }
            if (!Schema::hasColumn('savings_transactions', 'new_balance')) {
                $table->decimal('new_balance', 12, 2)->nullable()->after('previous_balance');
            }
            if (!Schema::hasColumn('savings_transactions', 'reviewed_by')) {
                $table->foreignId('reviewed_by')->nullable()->after('notes')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('savings_transactions', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            }
        });



        // Correct November 2026 session date to the first Saturday (Nov 7, 2026)
        DB::table('njangi_sessions')
            ->where('session_date', '2026-11-01')
            ->update([
                'session_date' => '2026-11-07',
                'title' => 'November 2026 General Session',
            ]);

        // Sync meeting attendance dates if column exists
        if (Schema::hasTable('meeting_attendances') && Schema::hasColumn('meeting_attendances', 'meeting_date')) {
            DB::table('meeting_attendances')
                ->where('meeting_date', '2026-11-01')
                ->update(['meeting_date' => '2026-11-07']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('savings_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('savings_transactions', 'reviewed_by')) {
                $table->dropForeign(['reviewed_by']);
                $table->dropColumn('reviewed_by');
            }
            if (Schema::hasColumn('savings_transactions', 'reviewed_at')) {
                $table->dropColumn('reviewed_at');
            }
            if (Schema::hasColumn('savings_transactions', 'previous_balance')) {
                $table->dropColumn('previous_balance');
            }
            if (Schema::hasColumn('savings_transactions', 'new_balance')) {
                $table->dropColumn('new_balance');
            }
        });
    }
};
