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
        Schema::create('njangi_disbursements', function (Blueprint $table) {
            $table->id();

            $table->foreignId('njangi_session_id')
                ->constrained('njangi_sessions')
                ->cascadeOnDelete();

            $table->foreignId('njangi_session_beneficiary_id')
                ->constrained('njangi_session_beneficiaries')
                ->cascadeOnDelete();

            $table->foreignId('njangi_cycle_member_id')
                ->constrained('njangi_cycle_members')
                ->cascadeOnDelete();

            // Gross amount allocated to beneficiary
            $table->decimal('gross_amount', 12, 2)->default(0);

            // Optional deductions
            $table->decimal('loan_deduction', 12, 2)->default(0);
            $table->decimal('penalty_deduction', 12, 2)->default(0);
            $table->decimal('other_deduction', 12, 2)->default(0);

            // Final amount actually paid out
            $table->decimal('net_amount', 12, 2)->default(0);

            $table->date('disbursement_date')->nullable();

            $table->enum('status', ['pending', 'approved', 'paid', 'cancelled'])
                ->default('pending');

            $table->string('payment_method')->nullable(); // cash, transfer, zelle, etc.
            $table->string('reference_number')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            // One payout record per beneficiary assignment
            $table->unique(['njangi_session_beneficiary_id']);

            // Prevent same member from getting multiple payout records in same session
            $table->unique(['njangi_session_id', 'njangi_cycle_member_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('njangi_disbursements');
    }
};