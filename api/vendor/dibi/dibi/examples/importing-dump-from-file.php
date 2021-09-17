<?php
declare(strict_types=1);
?>
<!DOCTYPE html><link rel="stylesheet" href="data/style.css">

<h1>Importing SQL Dump from File | Dibi</h1>

<?php

if (@!include __DIR__ . '/../vendor/autoload.php') {
	die('Install packages using `composer install`');
}


$dibi = new Dibi\Connection([
	'driver' => 'sqlite',
	'database' => 'data/sample.s3db',
]);


$count = $dibi->loadFile('compress.zlib://data/sample.dump.sql.gz');

echo 'Number of SQL commands:', $count;
