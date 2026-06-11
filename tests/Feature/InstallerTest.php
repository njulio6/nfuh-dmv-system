<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InstallerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Simulate application not installed by default in these tests
        config(['app.installed' => false]);

        // Ensure we start with clean installer lock file state in tests
        if (file_exists(storage_path('installed'))) {
            unlink(storage_path('installed'));
        }
    }

    protected function tearDown(): void
    {
        // Put back an empty lock file to avoid blocking actual local requests after tests
        file_put_contents(storage_path('installed'), 'Test Complete');

        parent::tearDown();
    }

    public function test_uninstalled_applications_redirect_to_installer(): void
    {
        // If config('app.installed') is false, home redirects to installer welcome
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
        // Set config app.installed to true
        config(['app.installed' => true]);

        $response = $this->get(route('install.welcome'));
        $response->assertRedirect('/');
    }

    public function test_failed_database_connection_redirects_back_with_error(): void
    {
        $response = $this->post(route('install.database.save'), [
            'host' => '127.0.0.1',
            'port' => '12345', // Dummy port to trigger connection failure
            'database' => 'invalid_db',
            'username' => 'invalid_user',
            'password' => 'invalid_pass',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_admin_creation_saves_admin_user_and_redirects(): void
    {
        // Truncate users or use memory database
        $response = $this->post(route('install.admin.save'), [
            'name' => 'Installer Admin',
            'email' => 'installer-admin@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('install.complete'));
        $this->assertDatabaseHas('users', [
            'email' => 'installer-admin@example.com',
            'name' => 'Installer Admin',
        ]);
    }

    public function test_complete_step_creates_installed_lock_file(): void
    {
        // Ensure clean lock file state before test
        if (file_exists(storage_path('installed'))) {
            unlink(storage_path('installed'));
        }

        $response = $this->get(route('install.complete'));
        $response->assertStatus(200);

        $this->assertFileExists(storage_path('installed'));
    }
}
