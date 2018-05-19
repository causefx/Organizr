<?php
if (isset($_POST['data']['plugin'])) {
	switch ($_POST['data']['plugin']) {
		case 'Invites/settings/get':
			if (qualifyRequest(1)) {
				$result['status'] = 'success';
				$result['statusText'] = 'success';
				$result['data'] = invitesGetSettings();
			} else {
				$result['status'] = 'error';
				$result['statusText'] = 'API/Token invalid or not set';
				$result['data'] = null;
			}
			break;
		case 'Invites/codes':
			$result['status'] = 'success';
			$result['statusText'] = 'success';
			$result['data'] = inviteCodes($_POST);
			break;
		default:
			//DO NOTHING!!
			break;
	}
}
if (isset($_GET['plugin']) && $_GET['plugin'] == 'Invites' && isset($_GET['cmd'])) {
	switch ($_GET['cmd']) {
		case 'Invites/settings/get':
			if (qualifyRequest(1)) {
				$result['status'] = 'success';
				$result['statusText'] = 'success';
				$result['data'] = invitesGetSettings();
			} else {
				$result['status'] = 'error';
				$result['statusText'] = 'API/Token invalid or not set';
				$result['data'] = null;
			}
			break;
		default:
			//Do NOTHING!
			break;
	}
}