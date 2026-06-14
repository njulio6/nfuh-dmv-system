<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class SystemToolsController extends Controller
{
    /**
     * Show the system tools dashboard.
     */
    public function index()
    {
        // Check PHP Version
        $phpVersion = PHP_VERSION;

        // Check DB Connection details safely
        $dbConnected = false;
        $dbName = '-';
        try {
            DB::connection()->getPdo();
            $dbConnected = true;
            $dbName = DB::connection()->getDatabaseName();
        } catch (\Exception $e) {
            // connection failed
        }

        // Get current migration status
        $migrationStatus = 'Unknown';
        try {
            Artisan::call('migrate:status');
            $migrationStatus = Artisan::output();
        } catch (\Exception $e) {
            $migrationStatus = 'Failed to get migration status: ' . $e->getMessage();
        }

        // Check storage symlink
        $storageLinkExists = file_exists(public_path('storage'));

        // Load logs from session
        $cmdLog = session('cmd_log');
        $cmdStatus = session('cmd_status'); // 'success' or 'error'

        return view('admin.tools', compact(
            'phpVersion',
            'dbConnected',
            'dbName',
            'migrationStatus',
            'storageLinkExists',
            'cmdLog',
            'cmdStatus'
        ));
    }

    /**
     * Run pending migrations.
     */
    public function runMigrations()
    {
        try {
            $exitCode = Artisan::call('migrate', ['--force' => true]);
            $output = Artisan::output();

            if ($exitCode === 0) {
                return redirect()->route('admin.tools')
                    ->with('success', 'Migrations ran successfully.')
                    ->with('cmd_log', $output)
                    ->with('cmd_status', 'success');
            } else {
                return redirect()->route('admin.tools')
                    ->with('error', 'Migrations failed with exit code: ' . $exitCode)
                    ->with('cmd_log', $output)
                    ->with('cmd_status', 'error');
            }
        } catch (\Exception $e) {
            return redirect()->route('admin.tools')
                ->with('error', 'Error executing migrations: ' . $e->getMessage())
                ->with('cmd_log', $e->getMessage() . "\n\n" . $e->getTraceAsString())
                ->with('cmd_status', 'error');
        }
    }

    /**
     * Clear application caches.
     */
    public function clearCache()
    {
        try {
            $log = "";

            Artisan::call('config:clear');
            $log .= "Config cache cleared.\n" . Artisan::output() . "\n";

            Artisan::call('cache:clear');
            $log .= "Application cache cleared.\n" . Artisan::output() . "\n";

            Artisan::call('route:clear');
            $log .= "Route cache cleared.\n" . Artisan::output() . "\n";

            Artisan::call('view:clear');
            $log .= "View cache cleared.\n" . Artisan::output() . "\n";

            return redirect()->route('admin.tools')
                ->with('success', 'Application caches cleared successfully.')
                ->with('cmd_log', $log)
                ->with('cmd_status', 'success');
        } catch (\Exception $e) {
            return redirect()->route('admin.tools')
                ->with('error', 'Error clearing cache: ' . $e->getMessage())
                ->with('cmd_log', $e->getMessage() . "\n\n" . $e->getTraceAsString())
                ->with('cmd_status', 'error');
        }
    }

    /**
     * Create public storage link.
     */
    public function storageLink()
    {
        try {
            $exitCode = Artisan::call('storage:link');
            $output = Artisan::output();

            if ($exitCode === 0) {
                return redirect()->route('admin.tools')
                    ->with('success', 'Storage link created successfully.')
                    ->with('cmd_log', $output)
                    ->with('cmd_status', 'success');
            } else {
                return redirect()->route('admin.tools')
                    ->with('error', 'Storage link command completed with code: ' . $exitCode)
                    ->with('cmd_log', $output)
                    ->with('cmd_status', 'error');
            }
        } catch (\Exception $e) {
            return redirect()->route('admin.tools')
                ->with('error', 'Error creating storage link: ' . $e->getMessage())
                ->with('cmd_log', $e->getMessage() . "\n\n" . $e->getTraceAsString())
                ->with('cmd_status', 'error');
        }
    }
}
