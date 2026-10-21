<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('njangi_sessions', function (Blueprint $table) {
            $table->string('qr_token', 64)->nullable()->unique()->after('status');
            $table->time('check_in_opens_at')->default('17:00:00')->after('qr_token');
            $table->time('check_in_closes_at')->default('21:00:00')->after('check_in_opens_at');
            $table->string('check_in_timezone', 50)->default('America/New_York')->after('check_in_closes_at');
        });

        // Generate tokens for existing sessions
        $sessions = DB::table('njangi_sessions')->whereNull('qr_token')->get();
        foreach ($sessions as $session) {
            DB::table('njangi_sessions')->where('id', $session->id)->update([
                'qr_token' => Str::random(32),
            ]);
        }

        Schema::table('meeting_attendances', function (Blueprint $table) {
            $table->string('check_in_source', 30)->default('officer_verification')->after('attendance_status');
            $table->timestamp('check_in_at')->nullable()->after('check_in_source');

            // Drop obsolete RSVP columns
            if (Schema::hasColumn('meeting_attendances', 'rsvp_status')) {
                $table->dropColumn(['rsvp_status', 'rsvp_notes', 'rsvp_at']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meeting_attendances', function (Blueprint $table) {
            $table->string('rsvp_status')->nullable()->after('attendance_status');
            $table->text('rsvp_notes')->nullable()->after('rsvp_status');
            $table->timestamp('rsvp_at')->nullable()->after('rsvp_notes');
            $table->dropColumn(['check_in_source', 'check_in_at']);
        });

        Schema::table('njangi_sessions', function (Blueprint $table) {
            $table->dropColumn(['qr_token', 'check_in_opens_at', 'check_in_closes_at', 'check_in_timezone']);
        });
    }
};
