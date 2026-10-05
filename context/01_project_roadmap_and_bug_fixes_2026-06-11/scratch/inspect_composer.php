<?php
$path = 'C:\\Users\\draki\\AppData\\Roaming\\Composer\\vendor\\bin\\composer';
if (file_exists($path)) {
    echo "$path exists\n";
    echo "Is file: " . (is_file($path) ? 'yes' : 'no') . "\n";
    echo "Is dir: " . (is_dir($path) ? 'yes' : 'no') . "\n";
    echo "Size: " . filesize($path) . "\n";
    echo "First 100 bytes:\n" . substr(file_get_contents($path), 0, 100) . "\n";
} else {
    echo "$path does not exist\n";
}

$composerDir = 'C:\\Users\\draki\\AppData\\Roaming\\Composer';
if (is_dir($composerDir)) {
    echo "\nListing files in $composerDir:\n";
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($composerDir));
    foreach ($it as $file) {
        if ($file->isFile()) {
            echo $file->getPathname() . "\n";
        }
    }
}
