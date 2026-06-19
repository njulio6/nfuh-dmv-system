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
        'min_savings_for_loan',
        'loan_guarantor_min',
        'loan_guarantor_max',
        'allow_mid_cycle_enrollment',
        'allow_mid_cycle_removal',
    ];

    protected $casts = [
        'beneficiary_count' => 'integer',
        'single_benefit_constraint' => 'boolean',
        'min_savings_for_loan' => 'float',
        'loan_guarantor_min' => 'integer',
        'loan_guarantor_max' => 'integer',
        'allow_mid_cycle_enrollment' => 'boolean',
        'allow_mid_cycle_removal' => 'boolean',
    ];
}
