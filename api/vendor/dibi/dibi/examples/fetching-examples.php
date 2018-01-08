<?php

if (@!include __DIR__ . '/../vendor/autoload.php') {
	die('Install dependencies using `composer install --dev`');
}

Tracy\Debugger::enable();

?>
<!DOCTYPE html><link rel="stylesheet" href="data/style.css">

<h1>Fetching Examples | dibi</h1>

<?php

dibi::connect([
	'driver' => 'sqlite3',
	'database' => 'data/sample.s3db',
]);


/*
TABLE products

product_id | title
-----------+----------
	1      | Chair
	2      | Table
	3      | Computer

*/


// fetch a single row
echo "<h2>fetch()</h2>\n";
$row = dibi::fetch('SELECT title FROM products');
Tracy\Dumper::dump($row); // Chair


// fetch a single value
echo "<h2>fetchSingle()</h2>\n";
$value = dibi::fetchSingle('SELECT title FROM products');
Tracy\Dumper::dump($value); // Chair


// fetch complete result set
echo "<h2>fetchAll()</h2>\n";
$all = dibi::fetchAll('SELECT * FROM products');
Tracy\Dumper::dump($all);


// fetch complete result set like association array
echo "<h2>fetchAssoc('title')</h2>\n";
$res = dibi::query('SELECT * FROM products');
$assoc = $res->fetchAssoc('title'); // key
Tracy\Dumper::dump($assoc);


// fetch complete result set like pairs key => value
echo "<h2>fetchPairs('product_id', 'title')</h2>\n";
$res = dibi::query('SELECT * FROM products');
$pairs = $res->fetchPairs('product_id', 'title');
Tracy\Dumper::dump($pairs);


// fetch row by row
echo "<h2>using foreach</h2>\n";
$res = dibi::query('SELECT * FROM products');
foreach ($res as $n => $row) {
	Tracy\Dumper::dump($row);
}


// more complex association array
$res = dibi::query('
	SELECT *
	FROM products
	INNER JOIN orders USING (product_id)
	INNER JOIN customers USING (customer_id)
');

echo "<h2>fetchAssoc('name|title')</h2>\n";
$assoc = $res->fetchAssoc('name|title'); // key
Tracy\Dumper::dump($assoc);

echo "<h2>fetchAssoc('name[]title')</h2>\n";
$res = dibi::query('SELECT * FROM products INNER JOIN orders USING (product_id) INNER JOIN customers USING (customer_id)');
$assoc = $res->fetchAssoc('name[]title'); // key
Tracy\Dumper::dump($assoc);

echo "<h2>fetchAssoc('name->title')</h2>\n";
$res = dibi::query('SELECT * FROM products INNER JOIN orders USING (product_id) INNER JOIN customers USING (customer_id)');
$assoc = $res->fetchAssoc('name->title'); // key
Tracy\Dumper::dump($assoc);
