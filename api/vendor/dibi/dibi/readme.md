[Dibi](https://dibiphp.com) - smart database layer for PHP  [![Buy me a coffee](https://files.nette.org/images/coffee1s.png)](https://nette.org/make-donation?to=dibi)
=========================================================

[![Downloads this Month](https://img.shields.io/packagist/dm/dibi/dibi.svg)](https://packagist.org/packages/dibi/dibi)
[![Tests](https://github.com/dg/dibi/workflows/Tests/badge.svg?branch=master)](https://github.com/dg/dibi/actions)
[![Build Status Windows](https://ci.appveyor.com/api/projects/status/github/dg/dibi?branch=master&svg=true)](https://ci.appveyor.com/project/dg/dibi/branch/master)
[![Latest Stable Version](https://poser.pugx.org/dibi/dibi/v/stable)](https://github.com/dg/dibi/releases)
[![License](https://img.shields.io/badge/license-New%20BSD-blue.svg)](https://github.com/dg/dibi/blob/master/license.md)


Introduction
------------

Database access functions in PHP are not standardised. This library
hides the differences between them, and above all, it gives you a very handy interface.


Support Me
----------

Do you like Dibi? Are you looking forward to the new features?

[![Buy me a coffee](https://files.nette.org/icons/donation-3.svg)](https://github.com/sponsors/dg)

Thank you!


Installation
------------

Install Dibi via Composer:

```bash
composer require dibi/dibi
```

The Dibi 4.2 requires PHP version 7.2 and supports PHP up to 8.3.


Usage
-----

Refer to the `examples` directory for examples. Dibi documentation is
available on the [homepage](https://dibiphp.com).


### Connecting to database

The database connection is represented by the object `Dibi\Connection`:

```php
$database = new Dibi\Connection([
	'driver'   => 'mysqli',
	'host'     => 'localhost',
	'username' => 'root',
	'password' => '***',
	'database' => 'table',
]);

$result = $database->query('SELECT * FROM users');
```

Alternatively, you can use the `dibi` static register, which maintains a connection object in a globally available storage and calls all the functions above it:

```php
dibi::connect([
	'driver'   => 'mysqli',
	'host'     => 'localhost',
	'username' => 'root',
	'password' => '***',
	'database' => 'test',
	'charset'  => 'utf8',
]);

$result = dibi::query('SELECT * FROM users');
```

In the event of a connection error, it throws `Dibi\Exception`.



### Queries

We query the database queries by the method `query()` which returns `Dibi\Result`. Rows are objects `Dibi\Row`.

You can try all the examples [online at the playground](https://repl.it/@DavidGrudl/dibi-playground).

```php
$result = $database->query('SELECT * FROM users');

foreach ($result as $row) {
	echo $row->id;
	echo $row->name;
}

// array of all rows
$all = $result->fetchAll();

// array of all rows, key is 'id'
$all = $result->fetchAssoc('id');

// associative pairs id => name
$pairs = $result->fetchPairs('id', 'name');

// the number of rows of the result, if known, or number of affected rows
$count = $result->getRowCount();
```

Method fetchAssoc() can return a more complex associative array.

You can easily add parameters to the query, note the question mark:

```php
$result = $database->query('SELECT * FROM users WHERE name = ? AND active = ?', $name, $active);

// or
$result = $database->query('SELECT * FROM users WHERE name = ?', $name, 'AND active = ?', $active););

$ids = [10, 20, 30];
$result = $database->query('SELECT * FROM users WHERE id IN (?)', $ids);
```

**WARNING: Never concatenate parameters to SQL. It would create a [SQL injection](https://en.wikipedia.org/wiki/SQL_injection)** vulnerability.
```
$result = $database->query('SELECT * FROM users WHERE id = ' . $id); // BAD!!!
```

Instead of a question mark, so-called modifiers can be used.

```php
$result = $database->query('SELECT * FROM users WHERE name = %s', $name);
```

In case of failure `query()` throws `Dibi\Exception`, or one of the descendants:

- `ConstraintViolationException` - violation of a table constraint
- `ForeignKeyConstraintViolationException` - invalid foreign key
- `NotNullConstraintViolationException` - violation of the NOT NULL condition
- `UniqueConstraintViolationException` - collides unique index

You can use also shortcuts:

```php
// returns associative pairs id => name, shortcut for query(...)->fetchPairs()
$pairs = $database->fetchPairs('SELECT id, name FROM users');

// returns array of all rows, shortcut for query(...)->fetchAll()
$rows = $database->fetchAll('SELECT * FROM users');

// returns row, shortcut for query(...)->fetch()
$row = $database->fetch('SELECT * FROM users WHERE id = ?', $id);

// returns field, shortcut for query(...)->fetchSingle()
$name = $database->fetchSingle('SELECT name FROM users WHERE id = ?', $id);
```


### Modifiers

In addition to the `?` wildcard char, we can also use modifiers:

| modifier | description
|----------|-----
| %s | string
| %sN | string, but '' translates as NULL
| %bin | binary data
| %b | boolean
| %i | integer
| %iN | integer, but 0 is translates as NULL
| %f | float
| %d | date (accepts DateTime, string or UNIX timestamp)
| %dt | datetime (accepts DateTime, string or UNIX timestamp)
| %n | identifier, ie the name of the table or column
| %N | identifier, treats period as a common character, ie alias or a database name (`%n AS %N` or `DROP DATABASE %N`)
| %SQL | SQL - directly inserts into SQL (the alternative is Dibi\Literal)
| %ex | SQL expression or array of expressions
| %lmt | special - adds LIMIT to the query
| %ofs | special - adds OFFSET to the query

Example:

```php
$result = $database->query('SELECT * FROM users WHERE name = %s', $name);
```

If $name is null, the NULL is inserted into the SQL statement.

If the variable is an array, the modifier is applied to all of its elements and they are inserted into SQL separated by commas:

```php
$ids = [10, '20', 30];
$result = $database->query('SELECT * FROM users WHERE id IN (%i)', $ids);
// SELECT * FROM users WHERE id IN (10, 20, 30)
```

The modifier `%n` is used if the table or column name is a variable. (Beware, do not allow the user to manipulate the content of such a variable):

```php
$table = 'blog.users';
$column = 'name';
$result = $database->query('SELECT * FROM %n WHERE %n = ?', $table, $column, $value);
// SELECT * FROM `blog`.`users` WHERE `name` = 'Jim'
```

Three special modifiers are available for LIKE:

| modifier | description
|----------|-----
| `%like~` | the expression starts with a string
| `%~like` | the expression ends with a string
| `%~like~` | the expression contains a string
| `%like` | the expression matches a string

Search for names beginning with a string:

```php
$result = $database->query('SELECT * FROM table WHERE name LIKE %like~', $query);
```


### Modifiers for arrays

The parameter entered in the SQL query can also be an array. These modifiers determine how to compile the SQL statement:

| modifier |   | result
|----------|---|-----
| %and   |        | `key1 = value1 AND key2 = value2 AND ...`
| %or    |        | `key1 = value1 OR key2 = value2 OR ...`
| %a     | assoc  | `key1 = value1, key2 = value2, ...`
| %l %in | list   | `(val1, val2, ...)`
| %v     | values | `(key1, key2, ...) VALUES (value1, value2, ...)`
| %m     | multi  | `(key1, key2, ...) VALUES (value1, value2, ...), (value1, value2, ...), ...`
| %by    | ordering | `key1 ASC, key2 DESC ...`
| %n     | names  | `key1, key2 AS alias, ...`

Example:

```php
$arr = [
	'a' => 'hello',
	'b'  => true,
];

$database->query('INSERT INTO table %v', $arr);
// INSERT INTO `table` (`a`, `b`) VALUES ('hello', 1)

$database->query('UPDATE `table` SET %a', $arr);
// UPDATE `table` SET `a`='hello', `b`=1
```

In the WHERE clause modifiers `%and` nebo `%or` can be used:

```php
$result = $database->query('SELECT * FROM users WHERE %and', [
	'name' => $name,
	'year' => $year,
]);
// SELECT * FROM users WHERE `name` = 'Jim' AND `year` = 1978
```

The modifier `%by` is used to sort, the keys show the columns, and the boolean value will determine whether to sort in ascending order:

```php
$result = $database->query('SELECT id FROM author ORDER BY %by', [
	'id' => true, // ascending
	'name' => false, // descending
]);
// SELECT id FROM author ORDER BY `id`, `name` DESC
```


### Insert, Update & Delete

We insert the data into an SQL query as an associative array. Modifiers and wildcards `?` are not required in these cases.

```php
$database->query('INSERT INTO users', [
	'name' => $name,
	'year' => $year,
]);
// INSERT INTO users (`name`, `year`) VALUES ('Jim', 1978)

$id = $database->getInsertId(); // returns the auto-increment of the inserted record

$id = $database->getInsertId($sequence); // or sequence value
```

Multiple INSERT:

```php
$database->query('INSERT INTO users', [
	'name' => 'Jim',
	'year' => 1978,
], [
	'name' => 'Jack',
	'year' => 1987,
]);
// INSERT INTO users (`name`, `year`) VALUES ('Jim', 1978), ('Jack', 1987)
```

Deleting:

```php
$database->query('DELETE FROM users WHERE id = ?', $id);

// returns the number of deleted rows
$affectedRows = $database->getAffectedRows();
```

Update:

```php
$database->query('UPDATE users SET', [
	'name' => $name,
	'year' => $year,
], 'WHERE id = ?', $id);
// UPDATE users SET `name` = 'Jim', `year` = 1978 WHERE id = 123

// returns the number of updated rows
$affectedRows = $database->getAffectedRows();
```

Insert an entry or update if it already exists:

```php
$database->query('INSERT INTO users', [
	'id' => $id,
	'name' => $name,
	'year' => $year,
], 'ON DUPLICATE KEY UPDATE %a', [ // here the modifier %a must be used
	'name' => $name,
	'year' => $year,
]);
// INSERT INTO users (`id`, `name`, `year`) VALUES (123, 'Jim', 1978)
//   ON DUPLICATE KEY UPDATE `name` = 'Jim', `year` = 1978
```


### Transaction

There are three methods for dealing with transactions:

```php
$database->begin();

$database->commit();

$database->rollback();
```


### Testing

In order to play with Dibi a little, there is a `test()` method that you pass parameters like to `query()`, but instead of executing the SQL statement, it is echoed on the screen.

The query results can be echoed as a table using `$result->dump()`.

These variables are also available:

```php
dibi::$sql; // the latest SQL query
dibi::$elapsedTime; // its duration in sec
dibi::$numOfQueries;
dibi::$totalTime;
```


### Complex queries

The parameter may also be an object `DateTime`.

```php
$result = $database->query('SELECT * FROM users WHERE created < ?', new DateTime);

$database->query('INSERT INTO users', [
	'created' => new DateTime,
]);
```

Or SQL literal:

```php
$database->query('UPDATE table SET', [
	'date' => $database->literal('NOW()'),
]);
// UPDATE table SET `date` = NOW()
```

Or an expression in which you can use `?` or modifiers:

```php
$database->query('UPDATE `table` SET', [
	'title' => $database::expression('SHA1(?)', 'secret'),
]);
// UPDATE `table` SET `title` = SHA1('secret')
```

When updating, modifiers can be placed directly in the keys:

```php
$database->query('UPDATE table SET', [
	'date%SQL' => 'NOW()', // %SQL means SQL ;)
]);
// UPDATE table SET `date` = NOW()
```

In conditions (ie, for `%and` and `%or` modifiers), it is not necessary to specify the keys:

```php
$result = $database->query('SELECT * FROM `table` WHERE %and', [
	'number > 10',
	'number < 100',
]);
// SELECT * FROM `table` WHERE (number > 10) AND (number < 100)
```

Modifiers or wildcards can also be used in expressions:

```php
$result = $database->query('SELECT * FROM `table` WHERE %and', [
	['number > ?', 10],  // or $database::expression('number > ?', 10)
	['number < ?', 100],
	['%or', [
		'left' => 1,
		'top' => 2,
	]],
]);
// SELECT * FROM `table` WHERE (number > 10) AND (number < 100) AND (`left` = 1 OR `top` = 2)
```

The `%ex` modifier inserts all items of the array into SQL:

```php
$result = $database->query('SELECT * FROM `table` WHERE %ex', [
	$database::expression('left = ?', 1),
	'AND',
	'top IS NULL',
]);
// SELECT * FROM `table` WHERE left = 1 AND top IS NULL
```


### Conditions in the SQL

Conditional SQL commands are controlled by three modifiers `%if`, `%else`, and `%end`. The `%if` must be at the end of the string representing SQL and is followed by the variable:

```php
$user = ???

$result = $database->query('
	SELECT *
	FROM table
	%if', isset($user), 'WHERE user=%s', $user, '%end
	ORDER BY name
');
```

The condition can be supplemented by the section `%else`:

```php
$result = $database->query('
	SELECT *
	FROM %if', $cond, 'one_table %else second_table
');
```

Conditions can nest together.


### Identifiers and strings in SQL

SQL itself goes through processing to meet the conventions of the database. The identifiers (names of tables and columns) can be entered into square brackets or backticks, strings are quoted with single or double quotation marks, but the server always sends what the database asks for. Example:

```php
$database->query("UPDATE `table` SET [status]='I''m fine'");
// MySQL: UPDATE `table` SET `status`='I\'m fine'
// ODBC:  UPDATE [table] SET [status]='I''m fine'
```

The quotation marks are duplicated inside the string in SQL.



### Result as associative array

Example: returns results as an associative field, where the key will be the value of the `id` field:

```php
$assoc = $result->fetchAssoc('id');
```

The greatest power of `fetchAssoc()` is reflected in a SQL query joining several tables with different types of joins. The database will make a flat table, fetchAssoc returns the shape.

Example: Let's take a customer and order table (N:M binding) and query:

```php
$result = $database->query('
  SELECT customer_id, customers.name, order_id, orders.number, ...
  FROM customers
  INNER JOIN orders USING (customer_id)
  WHERE ...
');
```

And we'd like to get a nested associative array by Customer ID and then Order ID:

```php
$all = $result->fetchAssoc('customer_id|order_id');

// we will iterate like this:
foreach ($all as $customerId => $orders) {
   foreach ($orders as $orderId => $order) {
	   ...
   }
}
```

An associative descriptor has a similar syntax as when you type the array by assigning it to PHP. Thus `'customer_id|order_id'` represents the assignment series `$all[$customerId][$orderId] = $row;` sequentially for all rows.

Sometimes it would be useful to associate by the customer's name instead of his ID:

```php
$all = $result->fetchAssoc('name|order_id');

// the elements then proceeds like this:
$order = $all['Arnold Rimmer'][$orderId];
```

But what if there are more customers with the same name? The table should be in the form of:

```php
$row = $all['Arnold Rimmer'][0][$orderId];
$row = $all['Arnold Rimmer'][1][$orderId];
...
```

So we can distinguish between multiple possible Rimmers using an array. The associative descriptor has a format similar to the assignment, with the sequence array representing `[]`:

```php
$all = $result->fetchAssoc('name[]order_id');

// we get all the Arnolds in the results
foreach ($all['Arnold Rimmer'] as $arnoldOrders) {
   foreach ($arnoldOrders as $orderId => $order) {
	   ...
   }
}
```

Returning to the example with the `customer_id|order_id` descriptor, we will try to list the orders of each customer:

```php
$all = $result->fetchAssoc('customer_id|order_id');

foreach ($all as $customerId => $orders) {
   echo "Customer $customerId":

   foreach ($orders as $orderId => $order) {
	   echo "ID number: $order->number";
	   // customer name is in $order->name
   }
}
```

It would be a nice to echo customer name too. But we would have to look for it in the `$orders` array. So let's adjust the results to such a shape:

```php
$all[$customerId]->name = 'John Doe';
$all[$customerId]->order_id[$orderId] = $row;
$all[$customerId]->order_id[$orderId2] = $row2;
```

So, between `$clientId` and `$orderId`, we will also insert an intermediate item. This time not the numbered indexes as we used to distinguish between individual Rimmers, but a database row. The solution is very similar, just remember that the row symbolizes the arrow:

```php
$all = $result->fetchAssoc('customer_id->order_id');

foreach ($all as $customerId => $row) {
   echo "Customer $row->name":

   foreach ($row->order_id as $orderId => $order) {
	   echo "ID number: $order->number";
   }
}
```



### Prefixes & substitutions

Table and column names can contain variable parts. You will first define:

```php
// create new substitution :blog:  ==>  wp_
$database->substitute('blog', 'wp_');
```

and then use it in SQL. Note that in SQL they are quoted by the colon:

```php
$database->query("UPDATE [:blog:items] SET [text]='Hello World'");
// UPDATE `wp_items` SET `text`='Hello World'
```


### Field data types

Dibi automatically detects the types of query columns and converts fields them to native PHP types. We can also specify the type manually. You can find the possible types in the `Dibi\Type` class.

```php
$result->setType('id', Dibi\Type::INTEGER); // id will be integer
$row = $result->fetch();

is_int($row->id) // true
```


### Logger

Dibi has a built-in logger that lets you track all SQL statements executed and measure the length of their duration. Activating the logger:

```php
$database->connect([
	'driver'   => 'sqlite',
	'database' => 'sample.sdb',
	'profiler' => [
		'file' => 'file.log',
	],
]);
```

A more versatile profiler is a Tracy panel that is activated when connected to Nette.



### Connect to [Nette](https://nette.org)

In the configuration file, we will register the DI extensions and add the `dibi` section to create the required objects and also the database panel in the [Tracy](https://tracy.nette.org) debugger bar.

```neon
extensions:
	dibi: Dibi\Bridges\Nette\DibiExtension22

dibi:
	host: localhost
	username: root
	password: ***
	database: foo
	lazy: true
```

Then the object of connection can be [obtained as a service from the container DI](https://doc.nette.org/di-usage), eg:

```php
class Model
{
	private $database;

	public function __construct(Dibi\Connection $database)
	{
		$this->database = $database;
	}
}
```
