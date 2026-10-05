<?php

define('LARAVEL_START', microtime(true));

// Load Composer autoloader
require __DIR__.'/../vendor/autoload.php';

// Boot the Laravel Application
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use Illuminate\Support\Facades\DB;

// Seeding implementation
try {
    $titles = [
        'HRH', 
        'Nformi', 
        'Tamfuh', 
        'Tar Kifu',
        'Ngwang', 
        'Ngwaye', 
        'Gwei', 
        'Lagham',
    ];

    $seeded = [];
    foreach ($titles as $title) {
        $exists = DB::table('member_ranks')->where('name', $title)->exists();
        if (!$exists) {
            DB::table('member_ranks')->insert([
                'name'       => $title,
                'level'      => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $seeded[] = $title;
        }
    }

    echo "<html><head><title>Title Seeder</title>";
    echo "<link rel='stylesheet' href='https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap'>";
    echo "<style>body{font-family: 'Inter', sans-serif; background-color: #09090b; color: #f4f4f5; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0;} .card{background-color: #18181b; border: 1px solid #27272a; padding: 2.5rem; border-radius: 1rem; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); max-width: 500px; text-align: center;} h1{color: #10b981; font-weight: 850; font-size: 1.5rem; margin-top: 0;} p{color: #a1a1aa; font-size: 0.875rem; line-height: 1.5;} .warning{background-color: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.2); color: #ef4444; padding: 0.75rem; border-radius: 0.5rem; font-size: 0.75rem; font-weight: 600; margin-top: 1.5rem;}</style>";
    echo "</head><body>";
    echo "<div class='card'>";
    echo "<h1>Seeding Completed Successfully!</h1>";
    
    if (empty($seeded)) {
        echo "<p>All traditional titles ('HRH', 'Nformi', 'Tamfuh', 'Tar Kifu', 'Ngwang', 'Ngwaye', 'Gwei', 'Lagham') already exist in the database.</p>";
    } else {
        echo "<p>Successfully seeded the following traditional titles: <strong>" . implode(', ', $seeded) . "</strong>.</p>";
    }
    
    echo "<div class='warning'>SECURITY WARNING: Please delete this file ('public/seed-titles.php') from your server immediately to prevent unauthorized database modifications.</div>";
    echo "</div></body></html>";

} catch (\Exception $e) {
    echo "<html><head><title>Seeder Error</title>";
    echo "<link rel='stylesheet' href='https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap'>";
    echo "<style>body{font-family: 'Inter', sans-serif; background-color: #09090b; color: #f4f4f5; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0;} .card{background-color: #18181b; border: 1px solid #ef4444; padding: 2.5rem; border-radius: 1rem; max-width: 600px;} h1{color: #ef4444; font-weight: 850; font-size: 1.5rem; margin-top: 0;} pre{background-color: #09090b; padding: 1rem; border-radius: 0.5rem; color: #f4f4f5; font-size: 0.75rem; overflow-x: auto; border: 1px solid #27272a;}</style>";
    echo "</head><body>";
    echo "<div class='card'>";
    echo "<h1>Database Seeding Error</h1>";
    echo "<p>An error occurred while seeding the traditional titles:</p>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "\n\n" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div></body></html>";
}
