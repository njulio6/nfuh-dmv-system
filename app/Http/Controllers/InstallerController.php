<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class InstallerController extends Controller
{
    public function welcome()
    {
        return view('installer.welcome');
    }

    public function requirements()
    {
        $requirements = [
            'PHP Version (>= 8.2)' => [
                'current' => PHP_VERSION,
                'status' => version_compare(PHP_VERSION, '8.2.0', '>='),
            ],
            'PDO MySQL Extension' => [
                'current' => extension_loaded('pdo_mysql') ? 'Enabled' : 'Disabled',
                'status' => extension_loaded('pdo_mysql'),
            ],
            'OpenSSL Extension' => [
                'current' => extension_loaded('openssl') ? 'Enabled' : 'Disabled',
                'status' => extension_loaded('openssl'),
            ],
            'Mbstring Extension' => [
                'current' => extension_loaded('mbstring') ? 'Enabled' : 'Disabled',
                'status' => extension_loaded('mbstring'),
            ],
            'XML Extension' => [
                'current' => extension_loaded('xml') ? 'Enabled' : 'Disabled',
                'status' => extension_loaded('xml'),
            ],
            'JSON Extension' => [
                'current' => extension_loaded('json') ? 'Enabled' : 'Disabled',
                'status' => extension_loaded('json'),
            ],
            'FileInfo Extension' => [
                'current' => extension_loaded('fileinfo') ? 'Enabled' : 'Disabled',
                'status' => extension_loaded('fileinfo'),
            ],
        ];

        $allPassed = true;
        foreach ($requirements as $req) {
            if (!$req['status']) {
                $allPassed = false;
                break;
            }
        }

        return view('installer.requirements', compact('requirements', 'allPassed'));
    }

    public function permissions()
    {
        $envWritable = is_writable(base_path('.env')) || (!file_exists(base_path('.env')) && is_writable(base_path()));
        
        $permissions = [
            '.env File / Root Directory' => [
                'path' => base_path('.env'),
                'status' => $envWritable,
            ],
            'Storage Directory' => [
                'path' => storage_path(),
                'status' => is_writable(storage_path()),
            ],
            'Bootstrap/Cache Directory' => [
                'path' => base_path('bootstrap/cache'),
                'status' => is_writable(base_path('bootstrap/cache')),
            ],
        ];

        $allPassed = !in_array(false, array_column($permissions, 'status'), true);

        return view('installer.permissions', compact('permissions', 'allPassed'));
    }

    public function database()
    {
        return view('installer.database');
    }

    public function saveDatabase(Request $request)
    {
        $validated = $request->validate([
            'host' => 'required|string',
            'port' => 'required|integer',
            'database' => 'required|string',
            'username' => 'required|string',
            'password' => 'nullable|string',
        ]);

        $host = $validated['host'];
        $port = $validated['port'];
        $database = $validated['database'];
        $username = $validated['username'];
        $password = $validated['password'] ?? '';

        // Test PDO Connection
        try {
            $connection = new \PDO("mysql:host={$host};port={$port};dbname={$database}", $username, $password);
            $connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Database connection failed: ' . $e->getMessage());
        }

        // Write to .env file
        try {
            $envPath = base_path('.env');
            $envContent = file_exists($envPath) 
                ? file_get_contents($envPath) 
                : file_get_contents(base_path('.env.example'));

            $replacements = [
                'DB_CONNECTION' => 'mysql',
                'DB_HOST' => $host,
                'DB_PORT' => $port,
                'DB_DATABASE' => $database,
                'DB_USERNAME' => $username,
                'DB_PASSWORD' => $password,
            ];

            // Generate APP_KEY if empty or not present in the .env content
            if (!preg_match('/^APP_KEY=base64:[A-Za-z0-9+\/]+=*$/m', $envContent)) {
                $newKey = 'base64:' . base64_encode(random_bytes(32));
                $replacements['APP_KEY'] = $newKey;
                Config::set('app.key', $newKey);
            }

            foreach ($replacements as $key => $val) {
                $pattern = "/^{$key}=.*/m";
                if (preg_match($pattern, $envContent)) {
                    $envContent = preg_replace($pattern, "{$key}={$val}", $envContent);
                } else {
                    $envContent .= "\n{$key}={$val}";
                }
            }

            file_put_contents($envPath, $envContent);
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Failed to update .env file: ' . $e->getMessage());
        }

        // Dynamically configure the active DB connection in-memory for the current request
        Config::set('database.default', 'mysql');
        Config::set('database.connections.mysql.host', $host);
        Config::set('database.connections.mysql.port', $port);
        Config::set('database.connections.mysql.database', $database);
        Config::set('database.connections.mysql.username', $username);
        Config::set('database.connections.mysql.password', $password);

        DB::purge('mysql');
        DB::reconnect('mysql');

        // Run migrations safely
        try {
            Artisan::call('migrate', ['--force' => true]);

            // Seed settings and structural records only if empty
            if (DB::table('settings')->count() === 0) {
                Artisan::call('db:seed', ['--force' => true]);
            }
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Failed to run migrations & seeders: ' . $e->getMessage());
        }

        return redirect()->route('install.admin');
    }

    public function admin()
    {
        return view('installer.admin');
    }

    public function saveAdmin(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Dynamically point to mysql connection in case config cache is active
        try {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'email_verified_at' => now(),
            ]);

            // Assign Spatie admin role to the super admin
            \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']);
            $user->assignRole('admin');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Failed to create administrator account: ' . $e->getMessage());
        }

        return redirect()->route('install.complete');
    }

    public function complete()
    {
        // Write the installation lock file
        try {
            file_put_contents(storage_path('installed'), 'Installation Complete: ' . now());
        } catch (\Exception $e) {
            return redirect()->route('install.admin')->with('error', 'Failed to write installation lock file: ' . $e->getMessage());
        }

        return view('installer.complete');
    }
}
