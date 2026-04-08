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
        Schema::create('njangi_contributions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('njangi_session_id')
                ->constrained('njangi_sessions')
                ->cascadeOnDelete();

            $table->foreignId('njangi_cycle_member_id')
                ->constrained('njangi_cycle_members')
                ->cascadeOnDelete();

            // Base monthly contribution expected from the member for this session
            $table->decimal('amount_due', 12, 2)->default(0);

            // Actual amount paid
            $table->decimal('amount_paid', 12, 2)->default(0);

            // Optional late fee / penalty / adjustment
            $table->decimal('penalty_amount', 12, 2)->default(0);
            $table->decimal('other_adjustment', 12, 2)->default(0);

            // Payment tracking
            $table->date('payment_date')->nullable();

            $table->enum('payment_status', ['pending', 'partial', 'paid', 'waived'])
                ->default('pending');

            $table->string('payment_method')->nullable(); // cash, transfer, zelle, etc.
            $table->string('reference_number')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            // One contribution record per member per session
            $table->unique(['njangi_session_id', 'njangi_cycle_member_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('njangi_contributions');
    }
};