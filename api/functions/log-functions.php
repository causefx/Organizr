<?php
function writeLoginLog($username, $authType)
{
	if (file_exists($GLOBALS['organizrLoginLog'])) {
		$getLog = str_replace("\r\ndate", "date", file_get_contents($GLOBALS['organizrLoginLog']));
		$gotLog = json_decode($getLog, true);
	}
	$logEntryFirst = array('logType' => 'login_log', 'auth' => array(array('date' => date("Y-m-d H:i:s"), 'utc_date' => $GLOBALS['currentTime'], 'username' => $username, 'ip' => userIP(), 'auth_type' => $authType)));
	$logEntry = array('date' => date("Y-m-d H:i:s"), 'utc_date' => $GLOBALS['currentTime'], 'username' => $username, 'ip' => userIP(), 'auth_type' => $authType);
	if (isset($gotLog)) {
		array_push($gotLog["auth"], $logEntry);
		$writeFailLog = str_replace("date", "\r\ndate", json_encode($gotLog));
	} else {
		$writeFailLog = str_replace("date", "\r\ndate", json_encode($logEntryFirst));
	}
	file_put_contents($GLOBALS['organizrLoginLog'], $writeFailLog);
}

function writeLog($type = 'error', $message, $username = null)
{
	$username = ($username) ? $username : $GLOBALS['organizrUser']['username'];
	if (file_exists($GLOBALS['organizrLog'])) {
		$getLog = str_replace("\r\ndate", "date", file_get_contents($GLOBALS['organizrLog']));
		$gotLog = json_decode($getLog, true);
	}
	$logEntryFirst = array('logType' => 'organizr_log', 'log_items' => array(array('date' => date("Y-m-d H:i:s"), 'utc_date' => $GLOBALS['currentTime'], 'type' => $type, 'username' => $username, 'ip' => userIP(), 'message' => $message)));
	$logEntry = array('date' => date("Y-m-d H:i:s"), 'utc_date' => $GLOBALS['currentTime'], 'type' => $type, 'username' => $username, 'ip' => userIP(), 'message' => $message);
	if (isset($gotLog)) {
		array_push($gotLog["log_items"], $logEntry);
		$writeFailLog = str_replace("date", "\r\ndate", json_encode($gotLog));
	} else {
		$writeFailLog = str_replace("date", "\r\ndate", json_encode($logEntryFirst));
	}
	file_put_contents($GLOBALS['organizrLog'], $writeFailLog);
}

function getLog($type, $reverse = true)
{
	switch ($type) {
		case 'login':
		case 'loginLog':
			$file = $GLOBALS['organizrLoginLog'];
			$parent = 'auth';
			break;
		case 'org':
		case 'organizrLog':
			$file = $GLOBALS['organizrLog'];
			$parent = 'log_items';
		// no break
		default:
			break;
	}
	if (!file_exists($file)) {
		return false;
	}
	$getLog = str_replace("\r\ndate", "date", file_get_contents($file));
	$gotLog = json_decode($getLog, true);
	return ($reverse) ? array_reverse($gotLog[$parent]) : $gotLog[$parent];
}