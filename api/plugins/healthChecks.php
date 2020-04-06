<?php
// PLUGIN INFORMATION
$GLOBALS['plugins'][]['healthChecks'] = array( // Plugin Name
	'name' => 'HealthChecks', // Plugin Name
	'author' => 'CauseFX', // Who wrote the plugin
	'category' => 'Utilities', // One to Two Word Description
	'link' => '', // Link to plugin info
	'license' => 'personal,business', // License Type use , for multiple
	'idPrefix' => 'HEALTHCHECKS', // html element id prefix
	'configPrefix' => 'HEALTHCHECKS', // config file prefix for array items without the hypen
	'version' => '1.0.0', // SemVer of plugin
	'image' => 'plugins/images/healthchecksio.png', // 1:1 non transparent image for plugin
	'settings' => true, // does plugin need a settings page? true or false
	'homepage' => false // Is plugin for use on homepage? true or false
);
// INCLUDE/REQUIRE FILES
// PLUGIN FUNCTIONS
function healthCheckTest($url)
{
	$success = false;
	$options = array('verify' => false, 'verifyname' => false, 'follow_redirects' => true, 'redirects' => 1);
	$headers = array('Token' => $GLOBALS['organizrAPI']);
	$url = qualifyURL($url);
	$response = Requests::get($url, $headers, $options);
	if ($response->success) {
		$success = true;
	}
	if ($response->status_code == 200) {
		$success = true;
	}
	return $success;
}

function healthCheckUUID($uuid, $pass = false)
{
	if (!$uuid || !$pass || $GLOBALS['HEALTHCHECKS-PingURL'] == '') {
		return false;
	}
	$url = qualifyURL($GLOBALS['HEALTHCHECKS-PingURL']);
	$uuid = '/' . $uuid;
	$path = !$pass ? '/fail' : '';
	$response = Requests::get($url . $uuid . $path, [], []);
	return $response;
}

function healthCheckRun()
{
	$continue = $GLOBALS['HEALTHCHECKS-all-items'] !== '' ? $GLOBALS['HEALTHCHECKS-all-items'] : false;
	if ($continue && $GLOBALS['HEALTHCHECKS-enabled'] && !empty($GLOBALS['HEALTHCHECKS-PingURL']) && qualifyRequest($GLOBALS['HEALTHCHECKS-Auth-include'])) {
		$allItems = [];
		foreach ($GLOBALS['HEALTHCHECKS-all-items'] as $k => $v) {
			
			if ($k !== false) {
				foreach ($v as $item) {
					$allItems[$k][$item['label']] = $item['value'];
				}
			}
		}
		foreach ($allItems as $k => $v) {
			if ($v['Enabled'] == 'false') {
				unset($allItems[$k]);
			}
			if (!$v['UUID']) {
				unset($allItems[$k]);
			}
		}
		foreach ($allItems as $k => $v) {
			$testLocal = $v['Internal URL'] !== '' ?? false;
			$testExternal = $v['External URL'] !== '' ?? false;
			$testBoth = ($testLocal && $testExternal) ?? false;
			$pass = false;
			if ($testLocal) {
				$allItems[$k]['results']['internal'] = (healthCheckTest($v['Internal URL'])) ? 'Success' : 'Error';
			}
			if ($testExternal) {
				$allItems[$k]['results']['external'] = (healthCheckTest($v['External URL'])) ? 'Success' : 'Error';
			}
			if ($testBoth) {
				if ($allItems[$k]['results']['external'] == 'Success' && $allItems[$k]['results']['internal'] == 'Success') {
					$pass = true;
				}
			} elseif ($testLocal) {
				if ($allItems[$k]['results']['internal'] == 'Success') {
					$pass = true;
				}
			} elseif ($testExternal) {
				if ($allItems[$k]['results']['external'] == 'Success') {
					$pass = true;
				}
			}
			healthCheckUUID($v['UUID'], 'true');
		}
		return $allItems;
	} else {
		'No Access';
	}
}

/* GET HEALTHCHECK SETTINGS */
function healthCheckGetSettings()
{
	return array(
		'Options' => array(
			array(
				'type' => 'select',
				'name' => 'HEALTHCHECKS-Auth-include',
				'label' => 'Minimum Authentication',
				'value' => $GLOBALS['HEALTHCHECKS-Auth-include'],
				'options' => groupSelect()
			),
			array(
				'type' => 'input',
				'name' => 'HEALTHCHECKS-PingURL',
				'label' => 'URL',
				'value' => $GLOBALS['HEALTHCHECKS-PingURL'],
				'help' => 'URL for HealthChecks Ping',
				'placeholder' => 'HealthChecks Ping URL'
			),
		),
		'Services' => array(
			array(
				'type' => 'arrayMultiple',
				'name' => 'HEALTHCHECKS-all-items',
				'label' => 'Services',
				'value' => $GLOBALS['HEALTHCHECKS-all-items']
			)
		)
	);
}
