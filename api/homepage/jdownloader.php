<?php

trait JDownloaderHomepageItem
{
	public function jDownloaderSettingsArray($infoOnly = false)
	{
		$homepageInformation = [
			'name' => 'JDownloader',
			'enabled' => strpos('personal', $this->config['license']) !== false,
			'image' => 'plugins/images/tabs/jdownloader.png',
			'category' => 'Downloader',
			'settingsArray' => __FUNCTION__
		];
		if ($infoOnly) {
			return $homepageInformation;
		}
		$homepageSettings = array(
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
                                        <li><i class="fa fa-chevron-right text-danger"></i> <a href="https://pypi.org/project/myjd-api/" target="_blank">Download [myjd-api] Module</a></li>
                                        <li><i class="fa fa-chevron-right text-danger"></i> Add <b>/api/myjd</b> to the URL if you are using <a href="https://pypi.org/project/FeedCrawler/" target="_blank">FeedCrawler</a></li>
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
						'name' => 'homepageJdownloaderEnabled',
						'label' => 'Enable',
						'value' => $this->config['homepageJdownloaderEnabled']
					),
					array(
						'type' => 'select',
						'name' => 'homepageJdownloaderAuth',
						'label' => 'Minimum Authentication',
						'value' => $this->config['homepageJdownloaderAuth'],
						'options' => $this->groupOptions
					)
				),
				'Connection' => array(
					array(
						'type' => 'input',
						'name' => 'jdownloaderURL',
						'label' => 'URL',
						'value' => $this->config['jdownloaderURL'],
						'help' => 'Please make sure to use local IP address and port - You also may use local dns name too.',
						'placeholder' => 'http(s)://hostname:port'
					)
				),
				'Misc Options' => array(
					array(
						'type' => 'select',
						'name' => 'jdownloaderRefresh',
						'label' => 'Refresh Seconds',
						'value' => $this->config['jdownloaderRefresh'],
						'options' => $this->timeOptions()
					),
					array(
						'type' => 'switch',
						'name' => 'jdownloaderCombine',
						'label' => 'Add to Combined Downloader',
						'value' => $this->config['jdownloaderCombine']
					),
				),
				'Test Connection' => array(
					array(
						'type' => 'blank',
						'label' => 'Please Save before Testing'
					),
					array(
						'type' => 'button',
						'label' => '',
						'icon' => 'fa fa-flask',
						'class' => 'pull-right',
						'text' => 'Test Connection',
						'attr' => 'onclick="testAPIConnection(\'jdownloader\')"'
					),
				)
			)
		);
		return array_merge($homepageInformation, $homepageSettings);
	}
	
	public function testConnectionJDownloader()
	{
		if (empty($this->config['jdownloaderURL'])) {
			$this->setAPIResponse('error', 'JDownloader URL is not defined', 422);
			return false;
		}
		$url = $this->qualifyURL($this->config['jdownloaderURL']);
		try {
			$options = $this->requestOptions($this->config['jdownloaderURL'], false, $this->config['jdownloaderRefresh']);
			$response = Requests::get($url, array(), $options);
			if ($response->success) {
				$this->setAPIResponse('success', 'API Connection succeeded', 200);
				return true;
			} else {
				$this->setAPIResponse('success', 'JDownloader Error Occurred', 500);
				return false;
			}
		} catch (Requests_Exception $e) {
			$this->writeLog('error', 'JDownloader Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
			$this->setAPIResponse('error', $e->getMessage(), 500);
			return false;
		};
	}
	
	public function jDownloaderHomepagePermissions($key = null)
	{
		$permissions = [
			'main' => [
				'enabled' => [
					'homepageJdownloaderEnabled'
				],
				'auth' => [
					'homepageJdownloaderAuth'
				],
				'not_empty' => [
					'jdownloaderURL'
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
	
	public function homepageOrderjdownloader()
	{
		if ($this->homepageItemPermissions($this->jDownloaderHomepagePermissions('main'))) {
			$loadingBox = ($this->config['jdownloaderCombine']) ? '' : '<div class="white-box homepage-loading-box"><h2 class="text-center" lang="en">Loading Download Queue...</h2></div>';
			$builder = ($this->config['jdownloaderCombine']) ? 'buildDownloaderCombined(\'jdownloader\');' : '$("#' . __FUNCTION__ . '").html(buildDownloader("jdownloader"));';
			return '
				<div id="' . __FUNCTION__ . '">
					' . $loadingBox . '
					<script>
		                // homepageOrderjdownloader
		                ' . $builder . '
		                homepageDownloader("jdownloader", "' . $this->config['jdownloaderRefresh'] . '");
		                // End homepageOrderjdownloader
	                </script>
				</div>
				';
		}
	}
	
	public function getJdownloaderHomepageQueue()
	{
		if (!$this->homepageItemPermissions($this->jDownloaderHomepagePermissions('main'), true)) {
			return false;
		}
		$url = $this->qualifyURL($this->config['jdownloaderURL']);
		try {
			$options = $this->requestOptions($this->config['jdownloaderURL'], false, $this->config['jdownloaderRefresh']);
			$response = Requests::get($url, array(), $options);
			if ($response->success) {
				$temp = json_decode($response->body, true);
				$packages = $temp['packages'];
				if ($packages['downloader']) {
					$api['content']['queueItems'] = $packages['downloader'];
				} else {
					$api['content']['queueItems'] = [];
				}
				if ($packages['linkgrabber_decrypted']) {
					$api['content']['grabberItems'] = $packages['linkgrabber_decrypted'];
				} else {
					$api['content']['grabberItems'] = [];
				}
				if ($packages['linkgrabber_failed']) {
					$api['content']['encryptedItems'] = $packages['linkgrabber_failed'];
				} else {
					$api['content']['encryptedItems'] = [];
				}
				if ($packages['linkgrabber_offline']) {
					$api['content']['offlineItems'] = $packages['linkgrabber_offline'];
				} else {
					$api['content']['offlineItems'] = [];
				}
				$api['content']['$status'] = array($temp['downloader_state'], $temp['grabber_collecting'], $temp['update_ready']);
			}
		} catch (Requests_Exception $e) {
			$this->writeLog('error', 'JDownloader Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
			$this->setAPIResponse('error', $e->getMessage(), 500);
			return false;
		};
		$api['content'] = isset($api['content']) ? $api['content'] : false;
		$this->setAPIResponse('success', null, 200, $api);
		return $api;
	}
}