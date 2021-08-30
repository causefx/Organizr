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
												<li><i class="fa fa-chevron-right text-danger"></i> <a href="https://pypi.org/project/myjd-api/" target="_blank">Download [myjd-api] Module</a></li>
												<li><i class="fa fa-chevron-right text-danger"></i> Add <b>/api/myjd</b> to the URL if you are using <a href="https://pypi.org/project/FeedCrawler/" target="_blank">FeedCrawler</a></li>
											</ul>
										</div>
									</div>
								</div>
							</div>
						</div>']
					),
				],
				'Enable' => [
					$this->settingsOption('enable', 'homepageJdownloaderEnabled'),
					$this->settingsOption('auth', 'homepageJdownloaderAuth'),
				],
				'Connection' => [
					$this->settingsOption('url', 'jdownloaderURL'),
					$this->settingsOption('blank'),
					$this->settingsOption('disable-cert-check', 'jdownloaderDisableCertCheck'),
					$this->settingsOption('use-custom-certificate', 'jdownloaderUseCustomCertificate'),
				],
				'Misc Options' => [
					$this->settingsOption('refresh', 'jdownloaderRefresh'),
					$this->settingsOption('combine', 'jdownloaderCombine'),
				],
				'Test Connection' => [
					$this->settingsOption('blank', null, ['label' => 'Please Save before Testing']),
					$this->settingsOption('test', 'jdownloader'),
				]
			]
		];
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
			$options = $this->requestOptions($url, $this->config['jdownloaderRefresh'], $this->config['jdownloaderDisableCertCheck'], $this->config['jdownloaderUseCustomCertificate']);
			$response = Requests::get($url, [], $options);
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
		return $this->homepageCheckKeyPermissions($key, $permissions);
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
			$options = $this->requestOptions($url, $this->config['jdownloaderRefresh'], $this->config['jdownloaderDisableCertCheck'], $this->config['jdownloaderUseCustomCertificate']);
			$response = Requests::get($url, [], $options);
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