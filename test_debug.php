<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Starting test...\n";

// Include all necessary files
$traitsPath = __DIR__ . '/api/functions/';
$homepagePath = __DIR__ . '/api/homepage/';

// Get all trait files
$traitFiles = glob($traitsPath . '*.php');
$homepageFiles = glob($homepagePath . '*.php');

echo "Loading trait files...\n";
foreach ($traitFiles as $file) {
    echo "Including: " . basename($file) . "\n";
    include_once $file;
}

echo "Loading homepage files...\n";
foreach ($homepageFiles as $file) {
    echo "Including: " . basename($file) . "\n";
    include_once $file;
}

echo "Loading main class...\n";
include_once __DIR__ . '/api/classes/organizr.class.php';

echo "Creating instance...\n";
try {
    $organizr = new Organizr();
    echo "SUCCESS: Class instantiated successfully!\n";
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}
