<?php
require 'c:/xampp/htdocs/nfuh-dmv-system/vendor/autoload.php';
$app = require_once 'c:/xampp/htdocs/nfuh-dmv-system/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Member;
use Illuminate\Support\Facades\DB;

echo "=====================================\n";
echo "1. USERS LIST\n";
echo "=====================================\n";
$users = User::all();
foreach ($users as $u) {
    echo "ID: {$u->id} | Name: {$u->name} | Email: {$u->email} | Roles: " . implode(', ', $u->roles->pluck('name')->toArray()) . "\n";
}

echo "\n=====================================\n";
echo "2. MEMBERS LIST\n";
echo "=====================================\n";
$members = Member::all();
foreach ($members as $m) {
    echo "ID: {$m->id} | Name: {$m->first_name} {$m->last_name} | Email: {$m->email} | Roles: " . implode(', ', $m->roles->pluck('name')->toArray()) . "\n";
}

echo "\n=====================================\n";
echo "3. SPATIE ROLES LIST\n";
echo "=====================================\n";
$roles = DB::table('roles')->get();
foreach ($roles as $r) {
    echo "ID: {$r->id} | Name: {$r->name}\n";
}

echo "\n=====================================\n";
echo "4. MODEL HAS ROLES LIST\n";
echo "=====================================\n";
$modelHasRoles = DB::table('model_has_roles')->get();
foreach ($modelHasRoles as $mhr) {
    echo "Model Type: {$mhr->model_type} | Model ID: {$mhr->model_id} | Role ID: {$mhr->role_id}\n";
}
