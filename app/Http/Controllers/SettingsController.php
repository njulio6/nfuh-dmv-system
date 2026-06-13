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
        ]);

        return view('settings.edit', compact('settings'));
    }

    public function update(Request $request)
    {
        $settings = Setting::first();
        if (!$settings) {
            $settings = new Setting();
        }

        $validated = $request->validate([
            'app_name' => ['required', 'string', 'max:255'],
            'logo_light' => ['nullable', 'image', 'mimes:png,jpg,jpeg,gif,webp', 'max:2048'],
            'logo_dark' => ['nullable', 'image', 'mimes:png,jpg,jpeg,gif,webp', 'max:2048'],
            'favicon' => ['nullable', 'file', 'mimes:ico,png,jpg,jpeg,gif,webp', 'max:1024'],
            'beneficiary_count' => ['required', 'integer', 'min:1'],
            'single_benefit_constraint' => ['nullable', 'boolean'],
            'min_savings_for_loan' => ['required', 'numeric', 'min:0'],
        ]);

        $settings->app_name = $validated['app_name'];
        $settings->beneficiary_count = $validated['beneficiary_count'];
        $settings->single_benefit_constraint = $request->has('single_benefit_constraint');
        $settings->min_savings_for_loan = $validated['min_savings_for_loan'];

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

        return redirect()
            ->route('settings.edit')
            ->with('success', 'Application settings updated successfully.');
    }
}
