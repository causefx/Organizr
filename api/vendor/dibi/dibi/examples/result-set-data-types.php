<?php
declare(strict_types=1);

use Dibi\Type;

if (@!include __DIR__ . '/../vendor/autoload.php') {
	die('Install dependencies using `composer install --dev`');
}

Tracy\Debugger::enable();

date_default_timezone_set('Europe/Prague');

?>
<!DOCTYPE html><link rel="stylesheet" href="data/style.css">

<h1>Result Set Data Types | Dibi</h1>

<?php

$dibi = new Dibi\Connection([
	'driver' => 'sqlite',
	'database' => 'data/sample.s3db',
]);


// using manual hints
$res = $dibi->query('SELECT * FROM [customers]');

$res->setType('customer_id', Type::INTEGER)
	->setType('added', Type::DATETIME)
	->setFormat(Type::DATETIME, 'Y-m-d H:i:s');


Tracy\Dumper::dump($res->fetch());
// outputs:
// Dibi\Row(3) {
//    customer_id => 1
//    name => "Dave Lister" (11)
//    added => "2007-03-11 17:20:03" (19)


// using auto-detection (works well with MySQL or other strictly typed databases)
$res = $dibi->query('SELECT * FROM [customers]');

Tracy\Dumper::dump($res->fetch());
// outputs:
// Dibi\Row(3) {
//    customer_id => 1
//    name => "Dave Lister" (11)
//    added => "2007-03-11 17:20:03" (19)
