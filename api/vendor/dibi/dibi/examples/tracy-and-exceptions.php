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


// throws error because SQL is bad
dibi::query('SELECT FROM customers WHERE customer_id < ?', 38);

?><!DOCTYPE html><link rel="stylesheet" href="data/style.css">

<h1>Tracy & SQL Exceptions | dibi</h1>

<p>Dibi can display and log exceptions via <a href="https://tracy.nette.org">Tracy</a>.</p>
