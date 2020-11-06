<?php
// PLUGIN INFORMATION
$GLOBALS['plugins'][]['healthChecks'] = array( // Plugin Name
	'name' => 'HealthChecks', // Plugin Name
	'author' => 'CauseFX', // Who wrote the plugin
	'category' => 'Utilities', // One to Two Word Description
	'link' => '', // Link to plugin info
	'license' => 'personal,business', // License Type use , for multiple
	'idPrefix' => 'HEALTHCHECKS', // html element id prefix
	'configPrefix' => 'HEALTHCHECKS', // config file prefix for array items without the hyphen
	'version' => '1.0.0', // SemVer of plugin
	'image' => 'plugins/images/healthchecksio.png', // 1:1 non transparent image for plugin
	'settings' => true, // does plugin need a settings page? true or false
	'homepage' => false // Is plugin for use on homepage? true or false
);

class HealthChecks extends Organizr
{
	public function _healthCheckPluginGetSettings()
	{
		return array(
			'Options' => array(
				array(
					'type' => 'select',
					'name' => 'HEALTHCHECKS-Auth-include',
					'label' => 'Minimum Authentication',
					'value' => $this->config['HEALTHCHECKS-Auth-include'],
					'options' => $this->groupSelect()
				),
				array(
					'type' => 'input',
					'name' => 'HEALTHCHECKS-PingURL',
					'label' => 'URL',
					'value' => $this->config['HEALTHCHECKS-PingURL'],
					'help' => 'URL for HealthChecks Ping',
					'placeholder' => 'HealthChecks Ping URL'
				),
			),
			'Services' => array(
				array(
					'type' => 'arrayMultiple',
					'name' => 'HEALTHCHECKS-all-items',
					'label' => 'Services',
					'value' => $this->config['HEALTHCHECKS-all-items']
				)
			)
		);
	}
	
	public function _healthCheckPluginTest($url)
	{
		$success = false;
		$options = array('verify' => false, 'verifyname' => false, 'follow_redirects' => true, 'redirects' => 1);
		$headers = array('Token' => $this->config['organizrAPI']);
		$url = $this->qualifyURL($url);
		$response = Requests::get($url, $headers, $options);
		if ($response->success) {
			$success = true;
		}
		if ($response->status_code == 200) {
			$success = true;
		}
		return $success;
	}
	
	public function _healthCheckPluginUUID($uuid, $pass = false)
	{
		if (!$uuid || !$pass || $this->config['HEALTHCHECKS-PingURL'] == '') {
			return false;
		}
		$url = $this->qualifyURL($this->config['HEALTHCHECKS-PingURL']);
		$uuid = '/' . $uuid;
		$path = !$pass ? '/fail' : '';
		$options = ($this->localURL($url)) ? array('verify' => false) : array('verify' => $this->getCert());
		return Requests::get($url . $uuid . $path, [], $options);
	}
	
	public function _healthCheckPluginRun()
	{
		$continue = $this->config['HEALTHCHECKS-all-items'] !== '' ? $this->config['HEALTHCHECKS-all-items'] : false;
		if (!$continue) {
			$this->setAPIResponse('error', 'No items are setup', 409);
		}
		if ($continue && $this->config['HEALTHCHECKS-enabled'] && !empty($this->config['HEALTHCHECKS-PingURL']) && $this->qualifyRequest($this->config['HEALTHCHECKS-Auth-include'])) {
			$allItems = [];
			foreach ($this->config['HEALTHCHECKS-all-items'] as $k => $v) {
				
				if ($k !== false) {
					foreach ($v as $item) {
						$allItems[$k][$item['label']] = $item['value'];
					}
				}
			}
			foreach ($allItems as $k => $v) {
				if ($v['Enabled'] == false) {
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
					$allItems[$k]['results']['internal'] = ($this->_healthCheckPluginTest($v['Internal URL'])) ? 'Success' : 'Error';
				}
				if ($testExternal) {
					if (($testBoth && $allItems[$k]['results']['internal'] == 'Error') || !$testBoth) {
						$allItems[$k]['results']['external'] = ($this->_healthCheckPluginTest($v['External URL'])) ? 'Success' : 'Error';
					} else {
						$allItems[$k]['results']['external'] = 'Not needed';
					}
				}
				if ($testBoth) {
					if ($allItems[$k]['results']['external'] == 'Success' || $allItems[$k]['results']['internal'] == 'Success') {
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
				$this->_healthCheckPluginUUID($v['UUID'], 'true');
			}
			$this->setAPIResponse('success', null, 200, $allItems);
		} else {
			$this->setAPIResponse('error', 'User does not have access', 401);
		}
	}
}
