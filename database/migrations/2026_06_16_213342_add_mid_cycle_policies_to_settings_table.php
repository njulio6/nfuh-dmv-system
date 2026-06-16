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
        Schema::table('settings', function (Blueprint $table) {
            $table->boolean('allow_mid_cycle_enrollment')->default(false)->after('loan_guarantor_max');
            $table->boolean('allow_mid_cycle_removal')->default(false)->after('allow_mid_cycle_enrollment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['allow_mid_cycle_enrollment', 'allow_mid_cycle_removal']);
        });
    }
};
