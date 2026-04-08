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
        Schema::create('njangi_session_beneficiaries', function (Blueprint $table) {
            $table->id();

            $table->foreignId('njangi_session_id')
                ->constrained('njangi_sessions')
                ->cascadeOnDelete();

            $table->foreignId('njangi_cycle_member_id')
                ->constrained('njangi_cycle_members')
                ->cascadeOnDelete();

            // Order of payout inside that specific session: 1, 2, 3, 4...
            $table->unsignedTinyInteger('beneficiary_slot');

            // Snapshot of draw order for clarity/reporting
            $table->unsignedTinyInteger('benefit_order')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            // Prevent same member from being assigned twice in same session
            $table->unique(['njangi_session_id', 'njangi_cycle_member_id']);

            // Prevent duplicate slot numbers in same session
            $table->unique(['njangi_session_id', 'beneficiary_slot']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('njangi_session_beneficiaries');
    }
};