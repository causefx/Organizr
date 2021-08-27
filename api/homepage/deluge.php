<?php

trait DelugeHomepageItem
{
	public function delugeSettingsArray($infoOnly = false)
	{
		$homepageInformation = [
			'name' => 'Deluge',
			'enabled' => strpos('personal', $this->config['license']) !== false,
			'image' => 'plugins/images/tabs/deluge.png',
			'category' => 'Downloader',
			'settingsArray' => __FUNCTION__
		];
		if ($infoOnly) {
			return $homepageInformation;
		}
		$homepageSettings = [
			'debug' => true,
			'settings' => [
				'FYI' => [
					$this->settingsOption('html', null, ['override' => 12, 'html' => '
						<div class="row">
							<div class="col-lg-12">
								<div class="panel panel-info">
									<div class="panel-heading">
										<span lang="en">Notice</span>
									</div>
									<div class="panel-wrapper collapse in" aria-expanded="true">
										<div class="panel-body">
											<ul class="list-icons">
												<li><i class="fa fa-chevron-right text-danger"></i> <a href="https://github.com/idlesign/deluge-webapi/tree/master/dist" target="_blank">Download Plugin</a></li>
												<li><i class="fa fa-chevron-right text-danger"></i> Open Deluge Web UI, go to "Preferences -> Plugins -> Install plugin" and choose egg file.</li>
												<li><i class="fa fa-chevron-right text-danger"></i> Activate WebAPI plugin </li>
											</ul>
										</div>
									</div>
								</div>
							</div>
						</div>']
					)
				],
				'Enable' => [
					$this->settingsOption('enable', 'homepageDelugeEnabled'),
					$this->settingsOption('auth', 'homepageDelugeAuth'),
				],
				'Connection' => [
					$this->settingsOption('url', 'delugeURL'),
					$this->settingsOption('password', 'delugePassword', ['help' => 'Note that using a blank password might not work correctly.']),
					$this->settingsOption('disable-cert-check', 'delugeDisableCertCheck'),
					$this->settingsOption('use-custom-certificate', 'delugeUseCustomCertificate'),
				],
				'Misc Options' => [
					$this->settingsOption('hide-seeding', 'delugeHideSeeding'),
					$this->settingsOption('hide-completed', 'delugeHideCompleted'),
					$this->settingsOption('refresh', 'delugeRefresh'),
					$this->settingsOption('combine', 'delugeCombine'),
				],
				'Test Connection' => [
					$this->settingsOption('blank', null, ['label' => 'Please Save before Testing. Note that using a blank password might not work correctly.']),
					$this->settingsOption('test', 'deluge'),
				]
			]
		];
		return array_merge($homepageInformation, $homepageSettings);
	}
	
	public function testConnectionDeluge()
	{
		if (empty($this->config['delugeURL'])) {
			$this->setAPIResponse('error', 'Deluge URL is not defined', 422);
			return false;
		}
		try {
			$options = $this->requestOptions($this->config['delugeURL'], $this->config['delugeRefresh'], $this->config['delugeDisableCertCheck'], $this->config['delugeUseCustomCertificate'], ['organizr_cert' => $this->getCert(), 'custom_cert' => $this->getCustomCert()]);
			$deluge = new deluge($this->config['delugeURL'], $this->decrypt($this->config['delugePassword']), $options);
			$torrents = $deluge->getTorrents(null, 'comment, download_payload_rate, eta, hash, is_finished, is_seed, message, name, paused, progress, queue, state, total_size, upload_payload_rate');
			$this->setAPIResponse('success', 'API Connection succeeded', 200);
			return true;
		} catch (Exception $e) {
			$this->writeLog('error', 'NZBGet Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
			$this->setAPIResponse('error', $e->getMessage(), 500);
			return false;
		}
		
	}
	
	public function delugeHomepagePermissions($key = null)
	{
		$permissions = [
			'main' => [
				'enabled' => [
					'homepageDelugeEnabled'
				],
				'auth' => [
					'homepageDelugeAuth'
				],
				'not_empty' => [
					'delugeURL'
				]
			]
		];
		if (array_key_exists($key, $permissions)) {
			return $permissions[$key];
		} elseif ($key == 'all') {
			return $permissions;
		} else {
			return [];
		}
	}
	
	public function homepageOrderdeluge()
	{
		if ($this->homepageItemPermissions($this->delugeHomepagePermissions('main'))) {
			$loadingBox = ($this->config['delugeCombine']) ? '' : '<div class="white-box homepage-loading-box"><h2 class="text-center" lang="en">Loading Download Queue...</h2></div>';
			$builder = ($this->config['delugeCombine']) ? 'buildDownloaderCombined(\'deluge\');' : '$("#' . __FUNCTION__ . '").html(buildDownloader("deluge"));';
			return '
				<div id="' . __FUNCTION__ . '">
					' . $loadingBox . '
					<script>
						// homepageOrderdeluge
						' . $builder . '
						homepageDownloader("deluge", "' . $this->config['delugeRefresh'] . '");
						// End homepageOrderdeluge
					</script>
				</div>
				';
		}
	}
	
	public function getDelugeHomepageQueue()
	{
		if (!$this->homepageItemPermissions($this->delugeHomepagePermissions('main'), true)) {
			return false;
		}
		try {
			$deluge = new deluge($this->config['delugeURL'], $this->decrypt($this->config['delugePassword']));
			$torrents = $deluge->getTorrents(null, 'comment, download_payload_rate, eta, hash, is_finished, is_seed, message, name, paused, progress, queue, state, total_size, upload_payload_rate');
			foreach ($torrents as $key => $value) {
				$tempStatus = $this->delugeStatus($value->queue, $value->state, $value->progress);
				if ($tempStatus == 'Seeding' && $this->config['delugeHideSeeding']) {
					//do nothing
				} elseif ($tempStatus == 'Finished' && $this->config['delugeHideCompleted']) {
					//do nothing
				} else {
					$api['content']['queueItems'][] = $value;
				}
			}
			$api['content']['queueItems'] = (empty($api['content']['queueItems'])) ? [] : $api['content']['queueItems'];
			$api['content']['historyItems'] = false;
		} catch (Excecption $e) {
			$this->writeLog('error', 'Deluge Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
			$this->setAPIResponse('error', $e->getMessage(), 500);
			return false;
		}
		$api['content'] = isset($api['content']) ? $api['content'] : false;
		$this->setAPIResponse('success', null, 200, $api);
		return $api;
	}
	
	public function delugeStatus($queued, $status, $state)
	{
		if ($queued == '-1' && $state == '100' && ($status == 'Seeding' || $status == 'Queued' || $status == 'Paused')) {
			$state = 'Seeding';
		} elseif ($state !== '100') {
			$state = 'Downloading';
		} else {
			$state = 'Finished';
		}
		return ($state) ? $state : $status;
	}
}