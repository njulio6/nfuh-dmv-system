<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingsSeeder extends Seeder
{
    /**
     * Seed the default application settings row.
     * This only inserts if no settings row exists yet.
     */
    public function run(): void
    {
        if (DB::table('settings')->count() === 0) {
            DB::table('settings')->insert([
                'app_name'                  => 'NFUH DMV',
                'logo_light_path'           => null,
                'logo_dark_path'            => null,
                'favicon_path'              => null,
                'beneficiary_count'         => 4,
                'single_benefit_constraint' => true,
                'created_at'                => now(),
                'updated_at'                => now(),
            ]);
        }
    }
}
