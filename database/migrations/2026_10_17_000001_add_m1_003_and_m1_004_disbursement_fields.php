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
        Schema::table('loan_requests', function (Blueprint $table) {
            $table->boolean('no_transaction_reference')->default(false)->after('disbursement_reference');
            $table->text('disbursement_reference_reason')->nullable()->after('no_transaction_reference');
        });

        Schema::table('savings_withdrawal_requests', function (Blueprint $table) {
            $table->foreignId('disbursed_by_user_id')->nullable()->after('reviewed_at')->constrained('users')->nullOnDelete();
            $table->date('disbursed_date')->nullable()->after('disbursed_at');
            $table->string('disbursement_method')->nullable()->after('disbursed_date');
            $table->text('disbursement_destination')->nullable()->after('disbursement_method');
            $table->string('disbursement_reference')->nullable()->after('disbursement_destination');
            $table->boolean('no_transaction_reference')->default(false)->after('disbursement_reference');
            $table->text('disbursement_reference_reason')->nullable()->after('no_transaction_reference');
            $table->string('disbursement_proof')->nullable()->after('disbursement_reference_reason');
            $table->text('disbursement_details')->nullable()->after('disbursement_proof');
            $table->foreignId('savings_transaction_id')->nullable()->after('disbursement_details')->constrained('savings_transactions')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('savings_withdrawal_requests', function (Blueprint $table) {
            $table->dropForeign(['disbursed_by_user_id']);
            $table->dropForeign(['savings_transaction_id']);
            $table->dropColumn([
                'disbursed_by_user_id',
                'disbursed_date',
                'disbursement_method',
                'disbursement_destination',
                'disbursement_reference',
                'no_transaction_reference',
                'disbursement_reference_reason',
                'disbursement_proof',
                'disbursement_details',
                'savings_transaction_id',
            ]);
        });

        Schema::table('loan_requests', function (Blueprint $table) {
            $table->dropColumn(['no_transaction_reference', 'disbursement_reference_reason']);
        });
    }
};
