<!DOCTYPE html><link rel="stylesheet" href="data/style.css">

<h1>Database Reflection | dibi</h1>

<?php

require __DIR__ . '/../src/loader.php';


dibi::connect([
	'driver' => 'sqlite3',
	'database' => 'data/sample.s3db',
]);


// retrieve database reflection
$database = dibi::getDatabaseInfo();

echo "<h2>Database '{$database->getName()}'</h2>\n";
echo "<ul>\n";
foreach ($database->getTables() as $table) {
	echo '<li>', ($table->isView() ? 'view' : 'table') . " {$table->getName()}</li>\n";
}
echo "</ul>\n";


// table reflection
$table = $database->getTable('products');

echo "<h2>Table '{$table->getName()}'</h2>\n";

echo "Columns\n";
echo "<ul>\n";
foreach ($table->getColumns() as $column) {
	echo "<li>{$column->getName()} <i>{$column->getNativeType()}</i> <code>{$column->getDefault()}</code></li>\n";
}
echo "</ul>\n";


echo 'Indexes';
echo "<ul>\n";
foreach ($table->getIndexes() as $index) {
	echo "<li>{$index->getName()} " . ($index->isPrimary() ? 'primary ' : '') . ($index->isUnique() ? 'unique' : '') . ' (';
	foreach ($index->getColumns() as $column) {
		echo $column->getName(), ', ';
	}
	echo ")</li>\n";
}
echo "</ul>\n";
