<!DOCTYPE html><link rel="stylesheet" href="data/style.css">

<h1>Using Logger | Dibi</h1>

<?php

if (@!include __DIR__ . '/../vendor/autoload.php') {
	die('Install packages using `composer install`');
}

date_default_timezone_set('Europe/Prague');


$dibi = new Dibi\Connection([
	'driver' => 'sqlite3',
	'database' => 'data/sample.s3db',
	// enable query logging to this file
	'profiler' => [
		'run' => true,
		'file' => 'data/log.sql',
	],
]);


try {
	$res = $dibi->query('SELECT * FROM [customers] WHERE [customer_id] = ?', 1);

	$res = $dibi->query('SELECT * FROM [customers] WHERE [customer_id] < ?', 5);

	$res = $dibi->query('SELECT FROM [customers] WHERE [customer_id] < ?', 38);
} catch (Dibi\Exception $e) {
	echo '<p>', get_class($e), ': ', $e->getMessage(), '</p>';
}


// outputs a log file
echo '<h2>File data/log.sql:</h2>';

echo '<pre>', file_get_contents('data/log.sql'), '</pre>';
