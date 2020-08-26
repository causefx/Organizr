<?php
reset($_GET);
$function = (key($_GET) ? str_replace("/", "_", key($_GET)) : false);
switch ($function) {
	case 'v1_auth':
		$group = ($_GET['group']) ?? 0;
		header('Location: v2/auth?group=' . $group);
		exit;
	default:
		// Forward everything to v2 api
		$result['status'] = "error";
		$result['statusText'] = "Please Use api/v2";
		break;
}
header('Location: v2/');
