<!DOCTYPE html><link rel="stylesheet" href="data/style.css">

<h1>Using Substitutions | dibi</h1>

<?php

require __DIR__ . '/../src/loader.php';


dibi::connect([
	'driver' => 'sqlite3',
	'database' => 'data/sample.s3db',
]);


// create new substitution :blog:  ==>  wp_
dibi::getSubstitutes()->blog = 'wp_';

dibi::test('SELECT * FROM [:blog:items]');
// -> SELECT * FROM [wp_items]


// create new substitution :: (empty)  ==>  my_
dibi::getSubstitutes()->{''} = 'my_';

dibi::test("UPDATE ::table SET [text]='Hello World'");
// -> UPDATE my_table SET [text]='Hello World'


// create substitutions using fallback callback
function substFallBack($expr)
{
	$const = 'SUBST_' . strtoupper($expr);
	if (defined($const)) {
		return constant($const);
	} else {
		throw new Exception("Undefined substitution :$expr:");
	}
}


// define callback
dibi::getSubstitutes()->setCallback('substFallBack');

// define substitutes as constants
define('SUBST_ACCOUNT', 'eshop_');
define('SUBST_ACTIVE', 7);

dibi::test("
	UPDATE :account:user
	SET name='John Doe', status=:active:
	WHERE id=", 7
);
// -> UPDATE eshop_user SET name='John Doe', status=7 WHERE id= 7
