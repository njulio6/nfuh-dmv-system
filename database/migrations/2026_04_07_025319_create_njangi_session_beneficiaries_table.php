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

        $table->foreignId('organization_id')
            ->constrained()
            ->cascadeOnDelete();

        $table->foreignId('njangi_session_id')
            ->constrained('njangi_sessions')
            ->cascadeOnDelete();

        $table->foreignId('njangi_cycle_member_id')
            ->constrained('njangi_cycle_members')
            ->cascadeOnDelete();

        $table->unsignedTinyInteger('beneficiary_slot');

        $table->unsignedInteger('benefit_order')->nullable();

        $table->text('notes')->nullable();

        $table->timestamps();

        $table->unique(['njangi_session_id', 'njangi_cycle_member_id']);
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