<?php
require_once '../api/functions.php';
define("API_HOST", getServerPath(false) . '');
$dirs = [
	'../api/plugins',
	'../api/v2',
];
$openapi = \OpenApi\scan($dirs);
ob_start();
header('Content-Type: application/json');
$json = $openapi->toJson();
echo $json;
//  Return the contents of the output buffer
$htmlStr = ob_get_contents();
// Clean (erase) the output buffer and turn off output buffering
ob_end_clean();
// Write final string to file
file_put_contents('./api.json', $htmlStr);
header("Location: home/");
echo $json;
