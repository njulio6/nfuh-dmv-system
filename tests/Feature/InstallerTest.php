<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstallerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure installed lock file does NOT exist → simulates "not installed"
        if (file_exists(storage_path('installed'))) {
            unlink(storage_path('installed'));
        }
        // Override config so the middleware uses the file check in test context
        config(['app.installed' => null]);
    }

    protected function tearDown(): void
    {
        // Always restore a lock file after installer tests so other tests
        // (and local browsing) aren't redirected to the installer
        config(['app.installed' => true]);
        file_put_contents(storage_path('installed'), 'Test Complete');

        parent::tearDown();
    }

    public function test_uninstalled_applications_redirect_to_installer(): void
    {
        // Lock file absent + config null → file_exists() check → not installed → redirect
        $response = $this->get('/');
        $response->assertRedirect(route('install.welcome'));
    }

    public function test_installer_routes_can_be_accessed_when_uninstalled(): void
    {
        $response = $this->get(route('install.welcome'));
        $response->assertStatus(200);

        $response = $this->get(route('install.requirements'));
        $response->assertStatus(200);

        $response = $this->get(route('install.permissions'));
        $response->assertStatus(200);
    }

    public function test_installed_applications_redirect_away_from_installer(): void
    {
        // Write the lock file → simulates installed application
        file_put_contents(storage_path('installed'), 'Mock Installed');

        $response = $this->get(route('install.welcome'));
        $response->assertRedirect('/');
    }

    public function test_failed_database_connection_redirects_back_with_error(): void
    {
        $response = $this->post(route('install.database.save'), [
            'host'     => '127.0.0.1',
            'port'     => '12345', // Invalid port → connection failure
            'database' => 'invalid_db',
            'username' => 'invalid_user',
            'password' => 'invalid_pass',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_admin_creation_saves_admin_user_and_redirects(): void
    {
        $response = $this->post(route('install.admin.save'), [
            'name'                  => 'Installer Admin',
            'email'                 => 'installer-admin@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('install.complete'));
        $this->assertDatabaseHas('users', [
            'email' => 'installer-admin@example.com',
            'name'  => 'Installer Admin',
        ]);

        $user = \App\Models\User::where('email', 'installer-admin@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('admin'));
    }

    public function test_complete_step_creates_installed_lock_file(): void
    {
        // Lock file is already absent from setUp
        $response = $this->get(route('install.complete'));
        $response->assertStatus(200);

        $this->assertFileExists(storage_path('installed'));
    }
}
