<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('njangi_contributions', function (Blueprint $table) {
            $table->foreignId('payment_submission_id')
                ->nullable()
                ->after('beneficiary_member_id')
                ->constrained('njangi_payment_submissions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('njangi_contributions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payment_submission_id');
        });
    }
};