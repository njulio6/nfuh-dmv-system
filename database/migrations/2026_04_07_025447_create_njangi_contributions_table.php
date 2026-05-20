<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('njangi_contributions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organization_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('njangi_cycle_id')
                ->constrained('njangi_cycles')
                ->cascadeOnDelete();

            $table->foreignId('njangi_session_id')
                ->constrained('njangi_sessions')
                ->cascadeOnDelete();

            $table->foreignId('contributor_member_id')
                ->constrained('members')
                ->cascadeOnDelete();

            $table->foreignId('beneficiary_member_id')
                ->constrained('members')
                ->cascadeOnDelete();

            $table->foreignId('payment_submission_id')
                ->nullable()
                ->constrained('njangi_payment_submissions')
                ->nullOnDelete();

            $table->decimal('amount', 12, 2);

            $table->date('payment_date')->nullable();

            $table->string('payment_method')->nullable(); // e.g. zelle
            $table->string('reference_number')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('njangi_contributions');
    }
};