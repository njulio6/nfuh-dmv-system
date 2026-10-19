<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('meeting_attendances', function (Blueprint $table) {
            $table->id();

            // Organization scope
            $table->foreignId('organization_id')
                ->constrained()
                ->cascadeOnDelete();

            // Parent session (meeting)
            $table->foreignId('njangi_session_id')
                ->constrained('njangi_sessions')
                ->cascadeOnDelete();

            // Target member
            $table->foreignId('member_id')
                ->constrained('members')
                ->cascadeOnDelete();

            // Advance Member RSVP
            $table->enum('rsvp_status', ['attending', 'not_attending', 'undecided'])
                ->default('undecided');
            $table->text('rsvp_notes')->nullable();
            $table->timestamp('rsvp_at')->nullable();

            // Official Roll-Call Verification by Executive Officer
            $table->enum('attendance_status', ['present', 'absent', 'excused'])
                ->nullable();
            $table->foreignId('verified_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('officer_notes')->nullable();

            $table->timestamps();

            // Ensure only one attendance/RSVP record per member per session
            $table->unique(['njangi_session_id', 'member_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meeting_attendances');
    }
};
