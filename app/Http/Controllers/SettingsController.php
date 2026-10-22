<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    public function edit()
    {
        $settings = Setting::first() ?? Setting::create([
            'app_name' => 'NFUH DMV',
            'beneficiary_count' => 4,
            'single_benefit_constraint' => true,
            'min_savings_for_loan' => 500.00,
            'loan_guarantor_min' => 1,
            'loan_guarantor_max' => 3,
            'default_loan_interest' => 1.00,
            'savings_withdrawal_fee_percent' => 2.00,
        ]);

        $subStatuses = \App\Models\LoanSubStatus::orderBy('name')->get();

        return view('settings.edit', compact('settings', 'subStatuses'));
    }

    public function update(Request $request)
    {
        $settings = Setting::first();
        if (!$settings) {
            $settings = new Setting();
        }

        $validated = $request->validate([
            'app_name'                 => ['required', 'string', 'max:255'],
            'theme_preset_light'       => ['nullable', 'string', 'max:50'],
            'primary_color_light'      => ['nullable', 'string', 'regex:/^#([a-f0-9]{6}|[a-f0-9]{3})$/i'],
            'secondary_color_light'    => ['nullable', 'string', 'regex:/^#([a-f0-9]{6}|[a-f0-9]{3})$/i'],
            'theme_preset_dark'        => ['nullable', 'string', 'max:50'],
            'primary_color_dark'       => ['nullable', 'string', 'regex:/^#([a-f0-9]{6}|[a-f0-9]{3})$/i'],
            'secondary_color_dark'     => ['nullable', 'string', 'regex:/^#([a-f0-9]{6}|[a-f0-9]{3})$/i'],
            'logo_light'              => ['nullable', 'image', 'mimes:png,jpg,jpeg,gif,webp', 'max:2048'],
            'logo_dark'               => ['nullable', 'image', 'mimes:png,jpg,jpeg,gif,webp', 'max:2048'],
            'favicon'                 => ['nullable', 'file', 'mimes:ico,png,jpg,jpeg,gif,webp', 'max:1024'],
            'beneficiary_count'       => ['required', 'integer', 'min:1'],
            'single_benefit_constraint' => ['nullable', 'boolean'],
            'min_savings_for_loan'    => ['required', 'numeric', 'min:0'],
            'loan_guarantor_min'      => ['required', 'integer', 'min:1'],
            'loan_guarantor_max'      => ['required', 'integer', 'min:1', 'gte:loan_guarantor_min'],
            'default_loan_interest'    => ['nullable', 'numeric', 'min:0', 'max:100'],
            'savings_withdrawal_fee_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'allow_mid_cycle_enrollment' => ['nullable', 'boolean'],
            'allow_mid_cycle_removal'    => ['nullable', 'boolean'],
            'restrict_checkin_to_cycle_members' => ['nullable', 'boolean'],
            'session_schedule_ordinal'   => ['nullable', 'string', 'in:first,second,third,fourth,last'],
            'session_schedule_weekday'   => ['nullable', 'string', 'in:Saturday,Sunday,Monday,Tuesday,Wednesday,Thursday,Friday'],
        ]);

        $settings->app_name = $validated['app_name'];
        if (isset($validated['theme_preset_light'])) {
            $settings->theme_preset_light = $validated['theme_preset_light'];
        }
        if (isset($validated['primary_color_light'])) {
            $settings->primary_color_light = $validated['primary_color_light'];
        }
        if (isset($validated['secondary_color_light'])) {
            $settings->secondary_color_light = $validated['secondary_color_light'];
        }
        if (isset($validated['theme_preset_dark'])) {
            $settings->theme_preset_dark = $validated['theme_preset_dark'];
        }
        if (isset($validated['primary_color_dark'])) {
            $settings->primary_color_dark = $validated['primary_color_dark'];
        }
        if (isset($validated['secondary_color_dark'])) {
            $settings->secondary_color_dark = $validated['secondary_color_dark'];
        }
        $settings->beneficiary_count = $validated['beneficiary_count'];
        $settings->single_benefit_constraint = $request->has('single_benefit_constraint');
        $settings->min_savings_for_loan = $validated['min_savings_for_loan'];
        $settings->loan_guarantor_min = $validated['loan_guarantor_min'];
        $settings->loan_guarantor_max = $validated['loan_guarantor_max'];
        if (isset($validated['default_loan_interest'])) {
            $settings->default_loan_interest = $validated['default_loan_interest'];
        }
        if (isset($validated['savings_withdrawal_fee_percent'])) {
            $settings->savings_withdrawal_fee_percent = $validated['savings_withdrawal_fee_percent'];
        }
        $settings->allow_mid_cycle_enrollment = $request->has('allow_mid_cycle_enrollment');
        $settings->allow_mid_cycle_removal = $request->has('allow_mid_cycle_removal');
        $settings->restrict_checkin_to_cycle_members = $request->has('restrict_checkin_to_cycle_members');
        $settings->session_schedule_ordinal = $validated['session_schedule_ordinal'] ?? 'first';
        $settings->session_schedule_weekday = $validated['session_schedule_weekday'] ?? 'Saturday';

        if ($request->hasFile('logo_light')) {
            if ($settings->logo_light_path) {
                Storage::disk('public')->delete($settings->logo_light_path);
            }
            $settings->logo_light_path = $request->file('logo_light')->store('settings', 'public');
        }

        if ($request->hasFile('logo_dark')) {
            if ($settings->logo_dark_path) {
                Storage::disk('public')->delete($settings->logo_dark_path);
            }
            $settings->logo_dark_path = $request->file('logo_dark')->store('settings', 'public');
        }

        if ($request->hasFile('favicon')) {
            if ($settings->favicon_path) {
                Storage::disk('public')->delete($settings->favicon_path);
            }
            $settings->favicon_path = $request->file('favicon')->store('settings', 'public');
        }

        $settings->save();

        $redirectParams = [];
        if ($request->filled('active_tab') && in_array($request->input('active_tab'), ['branding', 'financial', 'njangi'])) {
            $redirectParams['tab'] = $request->input('active_tab');
        }

        return redirect()
            ->route('settings.edit', $redirectParams)
            ->with('success', 'Application settings updated successfully.');
    }
}
