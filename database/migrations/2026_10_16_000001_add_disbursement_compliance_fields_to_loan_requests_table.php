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
            $table->foreignId('disbursed_by_user_id')->nullable()->after('disbursed_at')->constrained('users')->nullOnDelete();
            $table->string('disbursement_method')->nullable()->after('disbursed_by_user_id');
            $table->string('disbursement_reference')->nullable()->after('disbursement_method');
            $table->text('disbursement_destination')->nullable()->after('disbursement_reference');
            $table->date('disbursed_date')->nullable()->after('disbursement_destination');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loan_requests', function (Blueprint $table) {
            $table->dropForeign(['disbursed_by_user_id']);
            $table->dropColumn([
                'disbursed_by_user_id',
                'disbursement_method',
                'disbursement_reference',
                'disbursement_destination',
                'disbursed_date',
            ]);
        });
    }
};
