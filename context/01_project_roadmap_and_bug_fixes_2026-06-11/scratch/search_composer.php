<?php
$dirs = [
    'C:\\ProgramData',
    'C:\\Program Files',
    'C:\\Program Files (x86)',
    'C:\\Users\\draki\\AppData\\Roaming',
    'C:\\Users\\draki\\AppData\\Local',
    'C:\\xampp'
];

foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        continue;
    }
    echo "Searching in $dir...\n";
    try {
        $directory = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
        $iterator = new RecursiveIteratorIterator($directory, RecursiveIteratorIterator::SELF_FIRST);
        
        // Let's limit the depth of recursion to 4 levels to keep it fast
        $iterator->setMaxDepth(4);
        
        foreach ($iterator as $file) {
            $name = $file->getFilename();
            if (stripos($name, 'composer.bat') !== false || stripos($name, 'composer.phar') !== false || $name === 'composer') {
                if (!$file->isDir()) {
                    echo "Found: " . $file->getPathname() . "\n";
                }
            }
        }
    } catch (UnexpectedValueException $e) {
        // Permission error or similar, ignore
    } catch (Exception $e) {
        // Ignore other errors
    }
}
echo "Search finished.\n";
