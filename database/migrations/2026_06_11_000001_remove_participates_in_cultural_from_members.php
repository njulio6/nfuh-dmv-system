<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn('participates_in_cultural');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->boolean('participates_in_cultural')->default(true);
        });
    }
};
