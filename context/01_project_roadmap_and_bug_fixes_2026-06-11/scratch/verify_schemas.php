<?php
require 'c:/xampp/htdocs/nfuh-dmv-system/vendor/autoload.php';
$app = require_once 'c:/xampp/htdocs/nfuh-dmv-system/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;

echo "=====================================\n";
echo "COLUMNS OF njangi_contributions TABLE:\n";
echo "=====================================\n";
$columns = Schema::getColumnListing('njangi_contributions');
foreach ($columns as $column) {
    echo "- $column\n";
}

echo "\n=====================================\n";
echo "COLUMNS OF members TABLE:\n";
echo "=====================================\n";
$columns = Schema::getColumnListing('members');
foreach ($columns as $column) {
    echo "- $column\n";
}
