<?php

$db = new SQLite3("../chatpack.db");
$db->busyTimeout(60000);
$db->exec("PRAGMA journal_mode = wal;");

?>
