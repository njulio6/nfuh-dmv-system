<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'app_name',
        'theme_preset_light',
        'primary_color_light',
        'secondary_color_light',
        'theme_preset_dark',
        'primary_color_dark',
        'secondary_color_dark',
        'logo_light_path',
        'logo_dark_path',
        'favicon_path',
        'beneficiary_count',
        'single_benefit_constraint',
        'min_savings_for_loan',
        'loan_guarantor_min',
        'loan_guarantor_max',
        'default_loan_interest',
        'savings_withdrawal_fee_percent',
        'allow_mid_cycle_enrollment',
        'allow_mid_cycle_removal',
    ];

    protected $casts = [
        'beneficiary_count' => 'integer',
        'single_benefit_constraint' => 'boolean',
        'min_savings_for_loan' => 'float',
        'loan_guarantor_min' => 'integer',
        'loan_guarantor_max' => 'integer',
        'default_loan_interest' => 'float',
        'savings_withdrawal_fee_percent' => 'float',
        'allow_mid_cycle_enrollment' => 'boolean',
        'allow_mid_cycle_removal' => 'boolean',
    ];

    public function getPrimaryTextLightAttribute(): string
    {
        return $this->getContrastTextColor($this->primary_color_light ?? '#18181b');
    }

    public function getPrimaryTextDarkAttribute(): string
    {
        return $this->getContrastTextColor($this->primary_color_dark ?? '#ffffff');
    }

    public function getPrimaryRgbLightAttribute(): string
    {
        return $this->getHexToRgb($this->primary_color_light ?? '#18181b');
    }

    public function getPrimaryRgbDarkAttribute(): string
    {
        return $this->getHexToRgb($this->primary_color_dark ?? '#ffffff');
    }

    public function getSecondaryTextLightAttribute(): string
    {
        return $this->getContrastTextColor($this->secondary_color_light ?? '#52525b');
    }

    public function getSecondaryTextDarkAttribute(): string
    {
        return $this->getContrastTextColor($this->secondary_color_dark ?? '#a1a1aa');
    }

    public function getSecondaryRgbLightAttribute(): string
    {
        return $this->getHexToRgb($this->secondary_color_light ?? '#52525b');
    }

    public function getSecondaryRgbDarkAttribute(): string
    {
        return $this->getHexToRgb($this->secondary_color_dark ?? '#a1a1aa');
    }

    private function getHexToRgb(?string $hexColor): string
    {
        if (!$hexColor) return '24, 24, 27';
        $hex = ltrim($hexColor, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if (strlen($hex) !== 6) return '24, 24, 27';

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        return "{$r}, {$g}, {$b}";
    }

    private function getContrastTextColor(?string $hexColor): string
    {
        if (!$hexColor) return '#ffffff';
        $hex = ltrim($hexColor, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if (strlen($hex) !== 6) return '#ffffff';

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $yiq = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;
        return ($yiq >= 155) ? '#09090b' : '#ffffff';
    }
}
