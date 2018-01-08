<!DOCTYPE html><link rel="stylesheet" href="data/style.css">

<h1>Using Transactions | dibi</h1>

<?php

require __DIR__ . '/../src/loader.php';


dibi::connect([
	'driver' => 'sqlite3',
	'database' => 'data/sample.s3db',
]);


echo "<h2>Before</h2>\n";
dibi::query('SELECT * FROM [products]')->dump();
// -> 3 rows


dibi::begin();
dibi::query('INSERT INTO [products]', [
	'title' => 'Test product',
]);

echo "<h2>After INSERT</h2>\n";
dibi::query('SELECT * FROM [products]')->dump();


dibi::rollback(); // or dibi::commit();

echo "<h2>After rollback</h2>\n";
dibi::query('SELECT * FROM [products]')->dump();
// -> 3 rows again
