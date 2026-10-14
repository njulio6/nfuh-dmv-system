<?php

use App\Models\User;
use App\Models\Setting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

test('admin can access settings page and see modern ui sections', function () {
    Role::firstOrCreate(['name' => 'admin']);
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)
        ->get(route('settings.edit'));

    $response->assertOk();
    $response->assertSee('System & Branding Settings', false);
    $response->assertSee('Branding & Appearance', false);
    $response->assertSee('Financial Policies & Loans', false);
    $response->assertSee('Njangi & Governance', false);
    $response->assertSee('Loan Eligibility Balance');
    $response->assertSee('Default Loan Interest Rate');
    $response->assertSee('Savings Withdrawal Admin Fee');
    $response->assertSee('Guarantor Governance Limits');
    $response->assertSee('Single-Benefit Constraint');
    $response->assertSee('Mid-Cycle Enrollment');
    $response->assertSee('Mid-Cycle Removal');
    $response->assertSee('Save System Settings');
});

test('admin can update settings with logos and governance constraints', function () {
    Storage::fake('public');
    Role::firstOrCreate(['name' => 'admin']);
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $lightLogo = UploadedFile::fake()->image('light_logo.png', 200, 100);
    $darkLogo = UploadedFile::fake()->image('dark_logo.png', 200, 100);
    $favicon = UploadedFile::fake()->image('favicon.png', 32, 32);

    $response = $this->actingAs($admin)
        ->post(route('settings.update'), [
            'app_name' => 'NFUH DMV Premier',
            'beneficiary_count' => 5,
            'single_benefit_constraint' => '1',
            'min_savings_for_loan' => 1200.00,
            'loan_guarantor_min' => 2,
            'loan_guarantor_max' => 5,
            'default_loan_interest' => 2.50,
            'savings_withdrawal_fee_percent' => 3.50,
            'allow_mid_cycle_enrollment' => '1',
            'allow_mid_cycle_removal' => '1',
            'theme_preset_light' => 'indigo',
            'primary_color_light' => '#4f46e5',
            'theme_preset_dark' => 'custom',
            'primary_color_dark' => '#10b981',
            'logo_light' => $lightLogo,
            'logo_dark' => $darkLogo,
            'favicon' => $favicon,
        ]);

    $response->assertRedirect(route('settings.edit'));
    $response->assertSessionHas('success', 'Application settings updated successfully.');

    $settings = Setting::first();
    expect($settings->app_name)->toBe('NFUH DMV Premier');
    expect($settings->theme_preset_light)->toBe('indigo');
    expect($settings->primary_color_light)->toBe('#4f46e5');
    expect($settings->theme_preset_dark)->toBe('custom');
    expect($settings->primary_color_dark)->toBe('#10b981');
    expect((int)$settings->beneficiary_count)->toBe(5);
    expect($settings->single_benefit_constraint)->toBeTrue();
    expect((float)$settings->min_savings_for_loan)->toBe(1200.00);
    expect((int)$settings->loan_guarantor_min)->toBe(2);
    expect((int)$settings->loan_guarantor_max)->toBe(5);
    expect((float)$settings->default_loan_interest)->toBe(2.50);
    expect((float)$settings->savings_withdrawal_fee_percent)->toBe(3.50);
    expect($settings->allow_mid_cycle_enrollment)->toBeTrue();
    expect($settings->allow_mid_cycle_removal)->toBeTrue();

    Storage::disk('public')->assertExists($settings->logo_light_path);
    Storage::disk('public')->assertExists($settings->logo_dark_path);
    Storage::disk('public')->assertExists($settings->favicon_path);
});

test('settings validation fails if loan_guarantor_max is less than min', function () {
    Role::firstOrCreate(['name' => 'admin']);
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)
        ->post(route('settings.update'), [
            'app_name' => 'NFUH DMV',
            'beneficiary_count' => 4,
            'min_savings_for_loan' => 500.00,
            'loan_guarantor_min' => 4,
            'loan_guarantor_max' => 2, // invalid: less than min
        ]);

    $response->assertSessionHasErrors(['loan_guarantor_max']);
});

test('settings persists active tab in query parameters on update and render', function () {
    Role::firstOrCreate(['name' => 'admin']);
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    // GET with query param
    $response = $this->actingAs($admin)
        ->get(route('settings.edit', ['tab' => 'financial']));
    $response->assertOk();
    $response->assertSee('financial');

    // POST with active_tab brings user back to the same tab
    $response = $this->actingAs($admin)
        ->post(route('settings.update'), [
            'app_name' => 'NFUH DMV',
            'beneficiary_count' => 4,
            'min_savings_for_loan' => 500.00,
            'loan_guarantor_min' => 1,
            'loan_guarantor_max' => 3,
            'active_tab' => 'njangi',
        ]);

    $response->assertRedirect(route('settings.edit', ['tab' => 'njangi']));
});

test('admin can set same preset for both light and dark modes or choose separately', function () {
    Role::firstOrCreate(['name' => 'admin']);
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    // Test 1: Choose same preset (e.g. Royal Indigo for both)
    $response = $this->actingAs($admin)
        ->post(route('settings.update'), [
            'app_name' => 'NFUH DMV',
            'beneficiary_count' => 4,
            'min_savings_for_loan' => 500.00,
            'loan_guarantor_min' => 1,
            'loan_guarantor_max' => 3,
            'theme_preset_light' => 'indigo',
            'primary_color_light' => '#4f46e5',
            'theme_preset_dark' => 'indigo',
            'primary_color_dark' => '#6366f1',
        ]);

    $response->assertRedirect();
    $settings = Setting::first();
    expect($settings->theme_preset_light)->toBe('indigo');
    expect($settings->theme_preset_dark)->toBe('indigo');
    expect($settings->primary_color_light)->toBe('#4f46e5');
    expect($settings->primary_color_dark)->toBe('#6366f1');

    // Test 2: Choose separate manual custom colors for both modes
    $response = $this->actingAs($admin)
        ->post(route('settings.update'), [
            'app_name' => 'NFUH DMV',
            'beneficiary_count' => 4,
            'min_savings_for_loan' => 500.00,
            'loan_guarantor_min' => 1,
            'loan_guarantor_max' => 3,
            'theme_preset_light' => 'custom',
            'primary_color_light' => '#2b3a42',
            'secondary_color_light' => '#4f6d7a',
            'theme_preset_dark' => 'custom',
            'primary_color_dark' => '#f2994a',
            'secondary_color_dark' => '#f2c94c',
        ]);

    $response->assertRedirect();
    $settings->refresh();
    expect($settings->theme_preset_light)->toBe('custom');
    expect($settings->theme_preset_dark)->toBe('custom');
    expect($settings->primary_color_light)->toBe('#2b3a42');
    expect($settings->secondary_color_light)->toBe('#4f6d7a');
    expect($settings->primary_color_dark)->toBe('#f2994a');
    expect($settings->secondary_color_dark)->toBe('#f2c94c');
    expect($settings->secondary_rgb_light)->toBe('79, 109, 122');
    expect($settings->secondary_rgb_dark)->toBe('242, 201, 76');
    expect($settings->secondary_text_dark)->toBe('#09090b');
});

