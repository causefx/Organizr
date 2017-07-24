<?php
// Include functions
require_once('functions.php');
// Lazyload settings
$databaseConfig = configLazy('config/config.php');
// Get Action
if (isset($_POST['a'])) { $action = $_POST['a']; }
if (isset($_POST['k'])) { $key = $_POST['k']; }
if (isset($_GET['a'])) { $action = $_GET['a']; }
if (isset($_GET['k'])) { $key = $_GET['k']; }
unset($_POST['a']);
unset($_POST['k']);
//Set Default Result
$result = "An error has occurred";
//Check Key
if (!isset($key)) {
    exit(json_encode("No API Key set"));
}elseif (strtolower(ORGANIZRAPI) != strtolower($key)) {
    exit(json_encode("API Key mismatch"));
}
//Start API Call
if (isset($action)) {
    switch ($action) {
        case "1":
            $result = "test";
            break;
        case "2":
            $result = "other test";
            break;
        default:
            $result = "$action not defined";
    }
}
//return JSON array
exit(json_encode($result));
?>