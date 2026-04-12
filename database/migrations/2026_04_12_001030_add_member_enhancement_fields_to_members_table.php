<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->string('state_code', 2)->nullable()->after('address');
            $table->date('join_date')->nullable()->after('state_code');

            $table->string('next_of_kin_email')->nullable()->after('next_of_kin_phone');
            $table->text('next_of_kin_address')->nullable()->after('next_of_kin_email');

            $table->boolean('participates_in_njangi')->default(false)->after('status');
            $table->boolean('participates_in_savings')->default(false)->after('participates_in_njangi');
            $table->boolean('participates_in_cultural')->default(false)->after('participates_in_savings');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn([
                'state_code',
                'join_date',
                'next_of_kin_email',
                'next_of_kin_address',
                'participates_in_njangi',
                'participates_in_savings',
                'participates_in_cultural',
            ]);
        });
    }
};