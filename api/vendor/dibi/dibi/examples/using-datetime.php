<?php
declare(strict_types=1);
?>
<!DOCTYPE html><link rel="stylesheet" href="data/style.css">

<h1>Using DateTime | Dibi</h1>

<?php

if (@!include __DIR__ . '/../vendor/autoload.php') {
	die('Install packages using `composer install`');
}

date_default_timezone_set('Europe/Prague');


// CHANGE TO REAL PARAMETERS!
$dibi = new Dibi\Connection([
	'driver' => 'sqlite',
	'database' => 'data/sample.s3db',
	'formatDate' => "'Y-m-d'",
	'formatDateTime' => "'Y-m-d H-i-s'",
]);


// generate and dump SQL
$dibi->test('
	INSERT INTO [mytable]', [
		'id' => 123,
		'date' => new DateTime('12.3.2007'),
		'stamp' => new DateTime('23.1.2007 10:23'),
	]
);
// -> INSERT INTO [mytable] ([id], [date], [stamp]) VALUES (123, '2007-03-12', '2007-01-23 10-23-00')
