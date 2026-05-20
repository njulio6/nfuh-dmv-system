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
    Schema::create('njangi_sessions', function (Blueprint $table) {
        $table->id();

        // Organization scope (VERY IMPORTANT)
        $table->foreignId('organization_id')
            ->constrained()
            ->cascadeOnDelete();

        // Parent yearly cycle
        $table->foreignId('njangi_cycle_id')
            ->constrained('njangi_cycles')
            ->cascadeOnDelete();

        // Session sequence within the cycle: 1..12
        $table->unsignedTinyInteger('session_number');

        // Actual NFUH meeting date
        $table->date('session_date');

        // Optional tracking fields
        $table->string('title')->nullable(); // e.g. "January Session"
        $table->text('notes')->nullable();

        // Session state
        $table->enum('status', ['scheduled', 'open', 'closed', 'cancelled'])
            ->default('scheduled');

        $table->timestamps();

        // Constraints
        $table->unique(['njangi_cycle_id', 'session_number']);
        $table->unique(['njangi_cycle_id', 'session_date']);
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('njangi_sessions');
    }
};