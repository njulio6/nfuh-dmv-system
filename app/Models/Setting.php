<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'app_name',
        'logo_light_path',
        'logo_dark_path',
        'favicon_path',
        'beneficiary_count',
        'single_benefit_constraint',
    ];

    protected $casts = [
        'beneficiary_count' => 'integer',
        'single_benefit_constraint' => 'boolean',
    ];
}
