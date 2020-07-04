<?php
if (isset($_POST['data']['plugin'])) {
	switch ($_POST['data']['plugin']) {
		case 'HealthChecks/settings/get':
			if (qualifyRequest(1)) {
				$result['status'] = 'success';
				$result['statusText'] = 'success';
				$result['data'] = healthCheckGetSettings();
			} else {
				$result['status'] = 'error';
				$result['statusText'] = 'API/Token invalid or not set';
				$result['data'] = null;
			}
			break;
		case 'HealthChecks/run':
			if (qualifyRequest($GLOBALS['HEALTHCHECKS-Auth-include'])) {
				$result['status'] = 'success';
				$result['statusText'] = 'success';
				$result['data'] = healthCheckRun();
			} else {
				$result['status'] = 'error';
				$result['statusText'] = 'API/Token invalid or not set';
				$result['data'] = null;
			}
			break;
		default:
			//DO NOTHING!!
			break;
	}
}
if (isset($_GET['plugin']) && $_GET['plugin'] == 'HealthChecks' && isset($_GET['cmd'])) {
	switch ($_GET['cmd']) {
		case 'HealthChecks/settings/get':
			if (qualifyRequest(1)) {
				$result['status'] = 'success';
				$result['statusText'] = 'success';
				$result['data'] = healthCheckGetSettings();
			} else {
				$result['status'] = 'error';
				$result['statusText'] = 'API/Token invalid or not set';
				$result['data'] = null;
			}
			break;
		case 'HealthChecks/run':
			if (qualifyRequest($GLOBALS['HEALTHCHECKS-Auth-include'])) {
				$result['status'] = 'success';
				$result['statusText'] = 'success';
				$result['data'] = healthCheckRun();
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