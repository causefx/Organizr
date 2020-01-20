<?php
if (isset($_POST['data']['plugin'])) {
	switch ($_POST['data']['plugin']) {
		case 'chat/settings/get':
			if (qualifyRequest(1)) {
				$result['status'] = 'success';
				$result['statusText'] = 'success';
				$result['data'] = chatGetSettings();
			} else {
				$result['status'] = 'error';
				$result['statusText'] = 'API/Token invalid or not set';
				$result['data'] = null;
			}
			break;
		case 'chat/message':
			if (qualifyRequest($GLOBALS['CHAT-Auth-include'])) {
				$result['status'] = 'success';
				$result['statusText'] = 'success';
				$result['data'] = sendChatMessage($_POST);
			} else {
				$result['status'] = 'error';
				$result['statusText'] = 'API/Token invalid or not set';
				$result['data'] = null;
			}
			break;
		default:
			//DO NOTHING!!!
			break;
	}
}
if (isset($_GET['plugin']) && $_GET['plugin'] == 'chat' && isset($_GET['cmd'])) {
	switch ($_GET['cmd']) {
		case 'chat/settings/get':
			if (qualifyRequest(1)) {
				$result['status'] = 'success';
				$result['statusText'] = 'success';
				$result['data'] = chatGetSettings();
			} else {
				$result['status'] = 'error';
				$result['statusText'] = 'API/Token invalid or not set';
				$result['data'] = null;
			}
			break;
		case 'chat/message':
			if (qualifyRequest($GLOBALS['CHAT-Auth-include'])) {
				$result['status'] = 'success';
				$result['statusText'] = 'success';
				$result['data'] = getChatMessage();
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