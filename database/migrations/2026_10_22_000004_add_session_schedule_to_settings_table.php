<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('session_schedule_ordinal')->default('first')->after('restrict_checkin_to_cycle_members');
            $table->string('session_schedule_weekday')->default('Saturday')->after('session_schedule_ordinal');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['session_schedule_ordinal', 'session_schedule_weekday']);
        });
    }
};
