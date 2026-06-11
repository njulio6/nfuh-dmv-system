<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('app_name')->default('NFUH DMV');
            $table->string('logo_light_path')->nullable();
            $table->string('logo_dark_path')->nullable();
            $table->string('favicon_path')->nullable();
            $table->integer('beneficiary_count')->default(4);
            $table->boolean('single_benefit_constraint')->default(true);
            $table->timestamps();
        });

        // Seed default row
        DB::table('settings')->insert([
            'app_name' => 'NFUH DMV',
            'beneficiary_count' => 4,
            'single_benefit_constraint' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
