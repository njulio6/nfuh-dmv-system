<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->unsignedTinyInteger('loan_guarantor_min')->default(1)->after('min_savings_for_loan');
            $table->unsignedTinyInteger('loan_guarantor_max')->default(3)->after('loan_guarantor_min');
        });

        // Back-fill any existing rows with sensible defaults (matching previous hardcoded values)
        DB::table('settings')->whereNull('loan_guarantor_min')->orWhereNull('loan_guarantor_max')->update([
            'loan_guarantor_min' => 1,
            'loan_guarantor_max' => 3,
        ]);
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['loan_guarantor_min', 'loan_guarantor_max']);
        });
    }
};
