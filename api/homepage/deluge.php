<?php

trait DelugeHomepageItem
{
	public function delugeSettingsArray()
	{
		return array(
			'name' => 'Deluge',
			'enabled' => strpos('personal', $this->config['license']) !== false,
			'image' => 'plugins/images/tabs/deluge.png',
			'category' => 'Downloader',
			'settings' => array(
				'custom' => '
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
				</div>
				',
				'Enable' => array(
					array(
						'type' => 'switch',
						'name' => 'homepageDelugeEnabled',
						'label' => 'Enable',
						'value' => $this->config['homepageDelugeEnabled']
					),
					array(
						'type' => 'select',
						'name' => 'homepageDelugeAuth',
						'label' => 'Minimum Authentication',
						'value' => $this->config['homepageDelugeAuth'],
						'options' => $this->groupOptions
					)
				),
				'Connection' => array(
					array(
						'type' => 'input',
						'name' => 'delugeURL',
						'label' => 'URL',
						'value' => $this->config['delugeURL'],
						'help' => 'Please make sure to use local IP address and port - You also may use local dns name too.',
						'placeholder' => 'http(s)://hostname:port'
					),
					array(
						'type' => 'password',
						'name' => 'delugePassword',
						'label' => 'Password',
						'help' => 'Note that using a blank password might not work correctly.',
						'value' => $this->config['delugePassword']
					)
				),
				'Misc Options' => array(
					array(
						'type' => 'switch',
						'name' => 'delugeHideSeeding',
						'label' => 'Hide Seeding',
						'value' => $this->config['delugeHideSeeding']
					), array(
						'type' => 'switch',
						'name' => 'delugeHideCompleted',
						'label' => 'Hide Completed',
						'value' => $this->config['delugeHideCompleted']
					),
					array(
						'type' => 'select',
						'name' => 'homepageDownloadRefresh',
						'label' => 'Refresh Seconds',
						'value' => $this->config['homepageDownloadRefresh'],
						'options' => $this->timeOptions()
					),
					array(
						'type' => 'switch',
						'name' => 'delugeCombine',
						'label' => 'Add to Combined Downloader',
						'value' => $this->config['delugeCombine']
					),
				),
				'Test Connection' => array(
					array(
						'type' => 'blank',
						'label' => 'Please Save before Testing. Note that using a blank password might not work correctly.'
					),
					array(
						'type' => 'button',
						'label' => '',
						'icon' => 'fa fa-flask',
						'class' => 'pull-right',
						'text' => 'Test Connection',
						'attr' => 'onclick="testAPIConnection(\'deluge\')"'
					),
				)
			)
		);
	}
	
	public function testConnectionDeluge()
	{
		if (empty($this->config['delugeURL'])) {
			$this->setAPIResponse('error', 'Deluge URL is not defined', 422);
			return false;
		}
		try {
			$deluge = new deluge($this->config['delugeURL'], $this->decrypt($this->config['delugePassword']));
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
		                homepageDownloader("deluge", "' . $this->config['homepageDownloadRefresh'] . '");
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