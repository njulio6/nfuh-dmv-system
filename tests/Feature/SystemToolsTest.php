<?php

use App\Models\User;
use App\Models\Member;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guest is redirected to login from system tools', function () {
    $response = $this->get(route('admin.tools'));
    $response->assertRedirect(route('login'));
});

test('member without admin role is unauthorized from system tools', function () {
    $org = Organization::create(['name' => 'Test Org']);
    $user = User::create([
        'name' => 'Member User',
        'email' => 'member@example.com',
        'password' => bcrypt('password'),
    ]);

    Member::create([
        'organization_id' => $org->id,
        'member_code' => 'M-123',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'member@example.com',
        'phone' => '12345',
        'participates_in_savings' => true,
    ]);

    $response = $this->actingAs($user)->get(route('admin.tools'));
    $response->assertStatus(403);
});

test('admin user can access system tools dashboard', function () {
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']);
    $admin = User::create([
        'name' => 'Admin User',
        'email' => 'admin@example.com',
        'password' => bcrypt('password'),
    ]);
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->get(route('admin.tools'));
    $response->assertStatus(200);
    $response->assertSee('System Tools & Maintenance');
    $response->assertSee('PHP Version');
});
