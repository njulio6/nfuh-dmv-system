<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('secondary_color_light')->default('#52525b')->after('primary_color_light');
            $table->string('secondary_color_dark')->default('#a1a1aa')->after('primary_color_dark');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'secondary_color_light',
                'secondary_color_dark',
            ]);
        });
    }
};
