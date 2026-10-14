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
        Schema::table('loan_guarantors', function (Blueprint $table) {
            $table->boolean('terms_accepted')->default(false)->after('status');
            $table->timestamp('terms_accepted_at')->nullable()->after('terms_accepted');
            $table->string('terms_ip_address')->nullable()->after('terms_accepted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loan_guarantors', function (Blueprint $table) {
            $table->dropColumn(['terms_accepted', 'terms_accepted_at', 'terms_ip_address']);
        });
    }
};
