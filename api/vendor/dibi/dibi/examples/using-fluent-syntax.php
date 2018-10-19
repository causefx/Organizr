<?php
declare(strict_types=1);
?>
<!DOCTYPE html><link rel="stylesheet" href="data/style.css">

<h1>Using Fluent Syntax | Dibi</h1>

<?php

if (@!include __DIR__ . '/../vendor/autoload.php') {
	die('Install packages using `composer install`');
}

date_default_timezone_set('Europe/Prague');


$dibi = new Dibi\Connection([
	'driver' => 'sqlite',
	'database' => 'data/sample.s3db',
]);


$id = 10;
$record = [
	'title' => 'Super product',
	'price' => 318,
	'active' => true,
];

// SELECT ...
$dibi->select('product_id')->as('id')
	->select('title')
	->from('products')
	->innerJoin('orders')->using('(product_id)')
	->innerJoin('customers USING (customer_id)')
	->orderBy('title')
	->test();
// -> SELECT [product_id] AS [id] , [title] FROM [products] INNER JOIN [orders]
//    USING (product_id) INNER JOIN customers USING (customer_id) ORDER BY [title]


// SELECT ...
echo $dibi->select('title')->as('id')
	->from('products')
	->fetchSingle();
// -> Chair (as result of query: SELECT [title] AS [id] FROM [products])


// INSERT ...
$dibi->insert('products', $record)
	->setFlag('IGNORE')
	->test();
// -> INSERT IGNORE INTO [products] ([title], [price], [active]) VALUES ('Super product', 318, 1)


// UPDATE ...
$dibi->update('products', $record)
	->where('product_id = ?', $id)
	->test();
// -> UPDATE [products] SET [title]='Super product', [price]=318, [active]=1 WHERE product_id = 10


// DELETE ...
$dibi->delete('products')
	->where('product_id = ?', $id)
	->test();
// -> DELETE FROM [products] WHERE product_id = 10


// custom commands
$dibi->command()
	->update('products')
	->where('product_id = ?', $id)
	->set($record)
	->test();
// -> UPDATE [products] SET [title]='Super product', [price]=318, [active]=1 WHERE product_id = 10


$dibi->command()
	->truncate('products')
	->test();
// -> TRUNCATE [products]
