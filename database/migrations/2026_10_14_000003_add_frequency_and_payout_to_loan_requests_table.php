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
            $table->string('repayment_frequency')->default('monthly')->after('duration_months');
            $table->string('payout_method')->nullable()->after('purpose');
            $table->text('payout_details')->nullable()->after('payout_method');
            $table->text('rejection_reason')->nullable()->after('admin_notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loan_requests', function (Blueprint $table) {
            $table->dropColumn(['repayment_frequency', 'payout_method', 'payout_details', 'rejection_reason']);
        });
    }
};
