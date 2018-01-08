<?php

if (@!include __DIR__ . '/../vendor/autoload.php') {
	die('Install dependencies using `composer install --dev`');
}


// enable Tracy
Tracy\Debugger::enable();


$connection = dibi::connect([
	'driver' => 'sqlite3',
	'database' => 'data/sample.s3db',
	'profiler' => [
		'run' => true,
	],
]);


// add panel to debug bar
$panel = new Dibi\Bridges\Tracy\Panel;
$panel->register($connection);


// query will be logged
dibi::query('SELECT 123');

// result set will be dumped
Tracy\Debugger::barDump(dibi::fetchAll('SELECT * FROM customers WHERE customer_id < ?', 38), '[customers]');


?>
<!DOCTYPE html><link rel="stylesheet" href="data/style.css">

<style> html { background: url(data/arrow.png) no-repeat bottom right; height: 100%; } </style>

<h1>Tracy | dibi</h1>

<p>Dibi can log queries and dump variables to the <a href="https://tracy.nette.org">Tracy</a>.</p>
