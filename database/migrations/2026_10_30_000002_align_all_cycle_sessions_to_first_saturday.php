<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $sessions = DB::table('njangi_sessions')->get();

        foreach ($sessions as $session) {
            if (empty($session->session_date)) {
                continue;
            }

            $currentDate = Carbon::parse($session->session_date);

            // If the session was set to the 1st of the month or is not on Saturday, align to first Saturday
            if ($currentDate->day === 1 || !$currentDate->isSaturday()) {
                $firstSaturday = Carbon::parse('first Saturday of ' . $currentDate->format('F Y'))->toDateString();

                DB::table('njangi_sessions')
                    ->where('id', $session->id)
                    ->update([
                        'session_date' => $firstSaturday,
                    ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Date corrections do not require rollback
    }
};
