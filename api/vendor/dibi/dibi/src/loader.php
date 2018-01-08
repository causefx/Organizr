<?php

/**
 * This file is part of the "dibi" - smart database abstraction layer.
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */


if (PHP_VERSION_ID < 50404) {
	throw new Exception('Dibi requires PHP 5.4.4 or newer.');
}


spl_autoload_register(function ($class) {
	static $map = [
		'dibi' => 'dibi.php',
		'Dibi\Bridges\Nette\DibiExtension22' => 'Bridges/Nette/DibiExtension22.php',
		'Dibi\Bridges\Tracy\Panel' => 'Bridges/Tracy/Panel.php',
		'Dibi\Connection' => 'Connection.php',
		'Dibi\DataSource' => 'DataSource.php',
		'Dibi\DateTime' => 'DateTime.php',
		'Dibi\Driver' => 'interfaces.php',
		'Dibi\DriverException' => 'exceptions.php',
		'Dibi\Drivers\FirebirdDriver' => 'Drivers/FirebirdDriver.php',
		'Dibi\Drivers\SqlsrvDriver' => 'Drivers/SqlsrvDriver.php',
		'Dibi\Drivers\SqlsrvReflector' => 'Drivers/SqlsrvReflector.php',
		'Dibi\Drivers\MsSqlDriver' => 'Drivers/MsSqlDriver.php',
		'Dibi\Drivers\MsSqlReflector' => 'Drivers/MsSqlReflector.php',
		'Dibi\Drivers\MySqlDriver' => 'Drivers/MySqlDriver.php',
		'Dibi\Drivers\MySqliDriver' => 'Drivers/MySqliDriver.php',
		'Dibi\Drivers\MySqlReflector' => 'Drivers/MySqlReflector.php',
		'Dibi\Drivers\OdbcDriver' => 'Drivers/OdbcDriver.php',
		'Dibi\Drivers\OracleDriver' => 'Drivers/OracleDriver.php',
		'Dibi\Drivers\PdoDriver' => 'Drivers/PdoDriver.php',
		'Dibi\Drivers\PostgreDriver' => 'Drivers/PostgreDriver.php',
		'Dibi\Drivers\Sqlite3Driver' => 'Drivers/Sqlite3Driver.php',
		'Dibi\Drivers\SqliteReflector' => 'Drivers/SqliteReflector.php',
		'Dibi\Event' => 'Event.php',
		'Dibi\Exception' => 'exceptions.php',
		'Dibi\Fluent' => 'Fluent.php',
		'Dibi\HashMap' => 'HashMap.php',
		'Dibi\HashMapBase' => 'HashMap.php',
		'Dibi\Helpers' => 'Helpers.php',
		'Dibi\IDataSource' => 'interfaces.php',
		'Dibi\Literal' => 'Literal.php',
		'Dibi\Loggers\FileLogger' => 'Loggers/FileLogger.php',
		'Dibi\Loggers\FirePhpLogger' => 'Loggers/FirePhpLogger.php',
		'Dibi\NotImplementedException' => 'exceptions.php',
		'Dibi\NotSupportedException' => 'exceptions.php',
		'Dibi\PcreException' => 'exceptions.php',
		'Dibi\ProcedureException' => 'exceptions.php',
		'Dibi\Reflection\Column' => 'Reflection/Column.php',
		'Dibi\Reflection\Database' => 'Reflection/Database.php',
		'Dibi\Reflection\ForeignKey' => 'Reflection/ForeignKey.php',
		'Dibi\Reflection\Index' => 'Reflection/Index.php',
		'Dibi\Reflection\Result' => 'Reflection/Result.php',
		'Dibi\Reflection\Table' => 'Reflection/Table.php',
		'Dibi\Reflector' => 'interfaces.php',
		'Dibi\Result' => 'Result.php',
		'Dibi\ResultDriver' => 'interfaces.php',
		'Dibi\ResultIterator' => 'ResultIterator.php',
		'Dibi\Row' => 'Row.php',
		'Dibi\Strict' => 'Strict.php',
		'Dibi\Translator' => 'Translator.php',
		'Dibi\Type' => 'Type.php',
	], $old2new = [
		'Dibi' => 'dibi.php',
		'DibiColumnInfo' => 'Dibi\Reflection\Column',
		'DibiConnection' => 'Dibi\Connection',
		'DibiDatabaseInfo' => 'Dibi\Reflection\Database',
		'DibiDataSource' => 'Dibi\DataSource',
		'DibiDateTime' => 'Dibi\DateTime',
		'DibiDriverException' => 'Dibi\DriverException',
		'DibiEvent' => 'Dibi\Event',
		'DibiException' => 'Dibi\Exception',
		'DibiFileLogger' => 'Dibi\Loggers\FileLogger',
		'DibiFirebirdDriver' => 'Dibi\Drivers\FirebirdDriver',
		'DibiFirePhpLogger' => 'Dibi\Loggers\FirePhpLogger',
		'DibiFluent' => 'Dibi\Fluent',
		'DibiForeignKeyInfo' => 'Dibi\Reflection\ForeignKey',
		'DibiHashMap' => 'Dibi\HashMap',
		'DibiHashMapBase' => 'Dibi\HashMapBase',
		'DibiIndexInfo' => 'Dibi\Reflection\Index',
		'DibiLiteral' => 'Dibi\Literal',
		'DibiMsSql2005Driver' => 'Dibi\Drivers\SqlsrvDriver',
		'DibiMsSql2005Reflector' => 'Dibi\Drivers\SqlsrvReflector',
		'DibiMsSqlDriver' => 'Dibi\Drivers\MsSqlDriver',
		'DibiMsSqlReflector' => 'Dibi\Drivers\MsSqlReflector',
		'DibiMySqlDriver' => 'Dibi\Drivers\MySqlDriver',
		'DibiMySqliDriver' => 'Dibi\Drivers\MySqliDriver',
		'DibiMySqlReflector' => 'Dibi\Drivers\MySqlReflector',
		'DibiNotImplementedException' => 'Dibi\NotImplementedException',
		'DibiNotSupportedException' => 'Dibi\NotSupportedException',
		'DibiOdbcDriver' => 'Dibi\Drivers\OdbcDriver',
		'DibiOracleDriver' => 'Dibi\Drivers\OracleDriver',
		'DibiPcreException' => 'Dibi\PcreException',
		'DibiPdoDriver' => 'Dibi\Drivers\PdoDriver',
		'DibiPostgreDriver' => 'Dibi\Drivers\PostgreDriver',
		'DibiProcedureException' => 'Dibi\ProcedureException',
		'DibiResult' => 'Dibi\Result',
		'DibiResultInfo' => 'Dibi\Reflection\Result',
		'DibiResultIterator' => 'Dibi\ResultIterator',
		'DibiRow' => 'Dibi\Row',
		'DibiSqlite3Driver' => 'Dibi\Drivers\Sqlite3Driver',
		'DibiSqliteReflector' => 'Dibi\Drivers\SqliteReflector',
		'DibiTableInfo' => 'Dibi\Reflection\Table',
		'DibiTranslator' => 'Dibi\Translator',
		'IDataSource' => 'Dibi\IDataSource',
		'IDibiDriver' => 'Dibi\Driver',
		'IDibiReflector' => 'Dibi\Reflector',
		'IDibiResultDriver' => 'Dibi\ResultDriver',
		'Dibi\Drivers\MsSql2005Driver' => 'Dibi\Drivers\SqlsrvDriver',
		'Dibi\Drivers\MsSql2005Reflector' => 'Dibi\Drivers\SqlsrvReflector',
	];
	if (isset($map[$class])) {
		require __DIR__ . '/Dibi/' . $map[$class];
	} elseif (isset($old2new[$class])) {
		class_alias($old2new[$class], $class);
	}
});


// preload for compatiblity
array_map('class_exists', [
	'DibiConnection',
	'DibiDateTime',
	'DibiDriverException',
	'DibiEvent',
	'DibiException',
	'DibiFluent',
	'DibiLiteral',
	'DibiNotImplementedException',
	'DibiNotSupportedException',
	'DibiPcreException',
	'DibiProcedureException',
	'DibiResult',
	'DibiRow',
	'IDataSource',
	'IDibiDriver',
]);
