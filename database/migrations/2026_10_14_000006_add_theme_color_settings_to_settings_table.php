<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('theme_preset_light')->default('zinc')->after('app_name');
            $table->string('primary_color_light')->default('#18181b')->after('theme_preset_light');
            $table->string('theme_preset_dark')->default('zinc')->after('primary_color_light');
            $table->string('primary_color_dark')->default('#ffffff')->after('theme_preset_dark');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'theme_preset_light',
                'primary_color_light',
                'theme_preset_dark',
                'primary_color_dark',
            ]);
        });
    }
};
