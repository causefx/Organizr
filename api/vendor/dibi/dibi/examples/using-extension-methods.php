<?php

if (@!include __DIR__ . '/../vendor/autoload.php') {
	die('Install dependencies using `composer install --dev`');
}

Tracy\Debugger::enable();

?>
<!DOCTYPE html><link rel="stylesheet" href="data/style.css">

<h1>Using Extension Methods | dibi</h1>

<?php

dibi::connect([
	'driver' => 'sqlite3',
	'database' => 'data/sample.s3db',
]);


// using the "prototype" to add custom method to class Dibi\Result
Dibi\Result::extensionMethod('fetchShuffle', function (Dibi\Result $obj) {
	$all = $obj->fetchAll();
	shuffle($all);
	return $all;
});


// fetch complete result set shuffled
$res = dibi::query('SELECT * FROM [customers]');
$all = $res->fetchShuffle();
Tracy\Dumper::dump($all);
