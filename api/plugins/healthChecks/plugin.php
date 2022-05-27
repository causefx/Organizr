<?php
// PLUGIN INFORMATION
$GLOBALS['plugins']['HealthChecks'] = array( // Plugin Name
	'name' => 'HealthChecks', // Plugin Name
	'author' => 'CauseFX', // Who wrote the plugin
	'category' => 'Utilities', // One to Two Word Description
	'link' => '', // Link to plugin info
	'license' => 'personal,business', // License Type use , for multiple
	'idPrefix' => 'HEALTHCHECKS', // html element id prefix
	'configPrefix' => 'HEALTHCHECKS', // config file prefix for array items without the hyphen
	'version' => '1.0.0', // SemVer of plugin
	'image' => 'api/plugins/healthChecks/logo.png', // 1:1 non transparent image for plugin
	'settings' => true, // does plugin need a settings modal?
	'bind' => false, // use default bind to make settings page - true or false
	'api' => false, // api route for settings page
	'homepage' => false // Is plugin for use on homepage? true or false
);

class HealthChecks extends Organizr
{
	public function _healthCheckPluginGetSettings()
	{
		return array(
			'Cron' => array(
				/*array(
					'type' => 'html',
					'label' => '',
					'override' => 12,
					'html' => '
						<div class="row">
							<div class="col-lg-12">
								<div class="panel panel-info">
									<div class="panel-heading">
										<span lang="en">ATTENTION</span>
									</div>
									<div class="panel-wrapper collapse in" aria-expanded="true">
										<div class="panel-body">
											<h4 lang="en">Once this plugin is setup, you will need to setup a CRON job</h4>
											<br/>
											<span>
												<h4><b lang="en">CRON Job URL</b></h4>
												<code>' . $this->getServerPath() . 'api/v2/plugins/healthchecks/run</code><br/>
												<h5><b lang="en">Schedule</b></h5>
												<span lang="en">As often as you like - i.e. every 1 minute</span>
											</span>
										</div>
									</div>
								</div>
							</div>
						</div>
						'
				),*/
				$this->settingsOption('cron-file'),
				$this->settingsOption('blank'),
				$this->settingsOption('enable', 'HEALTHCHECKS-cron-run-enabled'),
				$this->settingsOption('cron', 'HEALTHCHECKS-cron-run-schedule')
			),
			'Options' => array(
				$this->settingsOption('auth', 'HEALTHCHECKS-Auth-include'),
				array(
					'type' => 'input',
					'name' => 'HEALTHCHECKS-PingURL',
					'label' => 'URL',
					'value' => $this->config['HEALTHCHECKS-PingURL'],
					'help' => 'URL for HealthChecks Ping',
					'placeholder' => 'HealthChecks Ping URL'
				),
				array(
					'type' => 'switch',
					'name' => 'HEALTHCHECKS-401-enabled',
					'label' => '401 Error as Success',
					'value' => $this->config['HEALTHCHECKS-401-enabled']
				),
				array(
					'type' => 'switch',
					'name' => 'HEALTHCHECKS-403-enabled',
					'label' => '403 Error as Success',
					'value' => $this->config['HEALTHCHECKS-403-enabled']
				),
			),
			'Connection' => array(
				array(
					'type' => 'input',
					'name' => 'healthChecksURL',
					'label' => 'URL',
					'value' => $this->config['healthChecksURL'],
					'help' => 'URL for HealthChecks API',
					'placeholder' => 'HealthChecks API URL'
				),
				array(
					'type' => 'password-alt',
					'name' => 'healthChecksToken',
					'label' => 'Token',
					'value' => $this->config['healthChecksToken']
				),
				array(
					'type' => 'html',
					'label' => '',
					'override' => 12,
					'html' => '
						<div class="row">
							<div class="col-lg-12">
								<div class="panel panel-danger">
									<div class="panel-heading">
										<span lang="en">ATTENTION</span>
									</div>
									<div class="panel-wrapper collapse in" aria-expanded="true">
										<div class="panel-body">
											<h4 lang="en">Please use a Full Access Token</h4>
											<br/>
											<div>
												<p lang="en">Do not use a Read-Only Token as that will not give a correct UUID for sending the results to HealthChecks.io</p>
												<p lang="en">Make sure to save before using the import button on Services tab</p>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
						'
				)
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
		$options = array('verify' => false, 'verifyname' => false, 'follow_redirects' => true, 'redirects' => 10, 'timeout' => 60);
		$headers = array('Token' => $this->config['organizrAPI']);
		$url = $this->qualifyURL($url);
		try {
			$response = Requests::get($url, $headers, $options);
			if ($response->success) {
				$success = true;
			}
			if ($response->status_code == 200) {
				$success = true;
			}
			if ($this->config['HEALTHCHECKS-401-enabled']) {
				if ($response->status_code == 401) {
					$success = true;
				}
			}
			if ($this->config['HEALTHCHECKS-403-enabled']) {
				if ($response->status_code == 403) {
					$success = true;
				}
			}
		} catch (Requests_Exception $e) {
			$this->setLoggerChannel('HealthChecks')->error($e);
			return false;
		}
		return $success;
	}

	public function _healthCheckSelfHostedURLValidation($url, $checkOnly = false)
	{
		$selfHosted = true;
		$url = $this->qualifyURL($url);
		if (stripos($url, 'hc-ping.com') == false) {
			if (stripos($url, '/ping') == false) {
				$url = $url . '/ping';
			}
		} else {
			$selfHosted = false;
		}
		return $checkOnly ? $selfHosted : $url;
	}

	public function _healthCheckPluginStartUUID($uuid)
	{
		if (!$uuid || $this->config['HEALTHCHECKS-PingURL'] == '') {
			return false;
		}
		$url = $this->_healthCheckSelfHostedURLValidation($this->config['HEALTHCHECKS-PingURL']);
		$uuid = '/' . $uuid;
		$options = ($this->localURL($url)) ? array('verify' => false) : array('verify' => $this->getCert());
		return Requests::get($url . $uuid . '/start', [], $options);
	}

	public function _healthCheckPluginUUID($uuid, $pass = false)
	{
		if (!$uuid || $this->config['HEALTHCHECKS-PingURL'] == '') {
			return false;
		}
		$url = $this->_healthCheckSelfHostedURLValidation($this->config['HEALTHCHECKS-PingURL']);
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
			$limit = 30;
			if (!empty($allItems)) {
				$limit = count($allItems) * 20;
			}
			set_time_limit($limit);
			foreach ($allItems as $k => $v) {
				$testLocal = $v['Internal URL'] !== '' ?? false;
				$testExternal = $v['External URL'] !== '' ?? false;
				$testBoth = ($testLocal && $testExternal) ?? false;
				$pass = false;
				if ($testLocal || $testExternal || $testBoth) {
					$this->_healthCheckPluginStartUUID($v['UUID']);
				}
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
				$this->_healthCheckPluginUUID($v['UUID'], $pass);
			}
			$this->setAPIResponse('success', null, 200, $allItems);
			return $allItems;
		} else {
			$this->setAPIResponse('error', 'User does not have access', 401);
		}
		return false;
	}
}