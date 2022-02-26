<?php

trait RTorrentHomepageItem
{
	public function rTorrentSettingsArray($infoOnly = false)
	{

		$homepageInformation = [
			'name' => 'rTorrent',
			'enabled' => strpos('personal', $this->config['license']) !== false,
			'image' => 'plugins/images/tabs/rTorrent.png',
			'category' => 'Downloader',
			'settingsArray' => __FUNCTION__
		];
		if ($infoOnly) {
			return $homepageInformation;
		}
		$xmlStatus = (extension_loaded('xmlrpc')) ? 'Installed' : 'Not Installed';
		$homepageSettings = [
			'debug' => true,
			'settings' => [
				'FYI' => [
					$this->settingsOption('html', null, ['label' => '', 'override' => 12,
						'html' => '
						<div class="row">
							<div class="col-lg-12">
								<div class="panel panel-info">
									<div class="panel-heading">
										<span lang="en">ATTENTION</span>
									</div>
									<div class="panel-wrapper collapse in" aria-expanded="true">
										<div class="panel-body">
											<h4 lang="en">This module requires XMLRPC</h4>
											<span lang="en">Status: [ <b>' . $xmlStatus . '</b> ]</span>
											<br/></br>
											<span lang="en">
												<h4><b>Note about API URL</b></h4>
												Organizr appends the url with <code>/RPC2</code> unless the URL ends in <code>.php</code><br/>
												<h5>Possible URLs:</h5>
												<li>http://localhost:8080</li>
												<li>https://domain.site/xmlrpc.php</li>
												<li>https://seedbox.site/rutorrent/plugins/httprpc/action.php</li>
											</span>
										</div>
									</div>
								</div>
							</div>
						</div>
						'
					]),
				],
				'Enable' => [
					$this->settingsOption('enable', 'homepagerTorrentEnabled'),
					$this->settingsOption('auth', 'homepagerTorrentAuth'),
				],
				'Connection' => [
					$this->settingsOption('url', 'rTorrentURL'),
					$this->settingsOption('input', 'rTorrentURLOverride', ['label' => 'rTorrent API URL Override', 'help' => 'Only use if you cannot connect.  Please make sure to use local IP address and port - You also may use local dns name too.', 'placeholder' => 'http(s)://hostname:port/xmlrpc']),
					$this->settingsOption('username', 'rTorrentUsername'),
					$this->settingsOption('password', 'rTorrentPassword'),
					$this->settingsOption('disable-cert-check', 'rTorrentDisableCertCheck'),
					$this->settingsOption('use-custom-certificate', 'rTorrentUseCustomCertificate'),
				],
				'Misc Options' => [
					$this->settingsOption('hide-seeding', 'rTorrentHideSeeding'),
					$this->settingsOption('hide-completed', 'rTorrentHideCompleted'),
					$this->settingsOption('select', 'rTorrentSortOrder', ['label' => 'Order', 'options' => $this->rTorrentSortOptions()]),
					$this->settingsOption('limit', 'rTorrentLimit'),
					$this->settingsOption('refresh', 'rTorrentRefresh'),
					$this->settingsOption('combine', 'rTorrentCombine'),
				],
				'Test Connection' => [
					$this->settingsOption('blank', null, ['label' => 'Please Save before Testing']),
					$this->settingsOption('test', 'rtorrent'),
				]
			]
		];
		return array_merge($homepageInformation, $homepageSettings);
	}

	public function testConnectionRTorrent()
	{
		if (empty($this->config['rTorrentURL']) && empty($this->config['rTorrentURLOverride'])) {
			$this->setAPIResponse('error', 'rTorrent URL is not defined', 422);
			return false;
		}
		try {
			$digest = (empty($this->config['rTorrentURLOverride'])) ? $this->qualifyURL($this->config['rTorrentURL'], true) : $this->qualifyURL($this->checkOverrideURL($this->config['rTorrentURL'], $this->config['rTorrentURLOverride']), true);
			$extraPath = (strpos($this->config['rTorrentURL'], '.php') !== false) ? '' : '/RPC2';
			$extraPath = (empty($this->config['rTorrentURLOverride'])) ? $extraPath : '';
			$url = $digest['scheme'] . '://' . $digest['host'] . $digest['port'] . $digest['path'] . $extraPath;
			$options = $this->requestOptions($url, null, $this->config['rTorrentDisableCertCheck'], $this->config['rTorrentUseCustomCertificate']);
			if ($this->config['rTorrentUsername'] !== '' && $this->decrypt($this->config['rTorrentPassword']) !== '') {
				$credentials = array('auth' => new Requests_Auth_Digest(array($this->config['rTorrentUsername'], $this->decrypt($this->config['rTorrentPassword']))));
				$options = array_merge($options, $credentials);
			}
			$data = xmlrpc_encode_request("system.listMethods", null);
			$response = Requests::post($url, [], $data, $options);
			if ($response->success) {
				$methods = xmlrpc_decode(str_replace('i8>', 'i4>', $response->body));
				if (count($methods) !== 0) {
					$this->setAPIResponse('success', 'API Connection succeeded', 200);
					return true;
				}
			}
			$this->setAPIResponse('error', 'rTorrent error occurred', 500);
			return false;
		} catch
		(Requests_Exception $e) {
			$this->writeLog('error', 'rTorrent Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
			$this->setAPIResponse('error', $e->getMessage(), 500);
			return false;
		}
	}

	public function rTorrentHomepagePermissions($key = null)
	{
		$permissions = [
			'main' => [
				'enabled' => [
					'homepagerTorrentEnabled'
				],
				'auth' => [
					'homepagerTorrentAuth'
				],
				'not_empty' => []
			]
		];
		return $this->homepageCheckKeyPermissions($key, $permissions);
	}

	public function homepageOrderrTorrent()
	{
		if ($this->homepageItemPermissions($this->rTorrentHomepagePermissions('main'))) {
			$loadingBox = ($this->config['rTorrentCombine']) ? '' : '<div class="white-box homepage-loading-box"><h2 class="text-center" lang="en">Loading Download Queue...</h2></div>';
			$builder = ($this->config['rTorrentCombine']) ? 'buildDownloaderCombined(\'rTorrent\');' : '$("#' . __FUNCTION__ . '").html(buildDownloader("rTorrent"));';
			return '
				<div id="' . __FUNCTION__ . '">
					' . $loadingBox . '
					<script>
						// homepageOrderrTorrent
						' . $builder . '
						homepageDownloader("rTorrent", "' . $this->config['rTorrentRefresh'] . '");
						// End homepageOrderrTorrent
					</script>
				</div>
				';
		}
	}

	public function checkOverrideURL($url, $override)
	{
		if (strpos($override, $url) !== false) {
			return $override;
		} else {
			return $url . $override;
		}
	}

	public function rTorrentStatus($completed, $state, $status)
	{
		if ($completed && $state && $status == 'seed') {
			$state = 'Seeding';
		} elseif (!$completed && !$state && $status == 'leech') {
			$state = 'Stopped';
		} elseif (!$completed && $state && $status == 'leech') {
			$state = 'Downloading';
		} elseif ($completed && !$state && $status == 'seed') {
			$state = 'Finished';
		}
		return ($state) ? $state : $status;
	}

	public function getRTorrentHomepageQueue()
	{
		if (empty($this->config['rTorrentURL']) && empty($this->config['rTorrentURLOverride'])) {
			$this->setAPIResponse('error', 'rTorrent URL is not defined', 422);
			return false;
		}
		if (!$this->homepageItemPermissions($this->rTorrentHomepagePermissions('main'), true)) {
			return false;
		}
		try {
			if ($this->config['rTorrentLimit'] == '0') {
				$this->config['rTorrentLimit'] = '1000';
			}
			$torrents = array();
			$digest = (empty($this->config['rTorrentURLOverride'])) ? $this->qualifyURL($this->config['rTorrentURL'], true) : $this->qualifyURL($this->checkOverrideURL($this->config['rTorrentURL'], $this->config['rTorrentURLOverride']), true);
			$extraPath = (strpos($this->config['rTorrentURL'], '.php') !== false) ? '' : '/RPC2';
			$extraPath = (empty($this->config['rTorrentURLOverride'])) ? $extraPath : '';
			$url = $digest['scheme'] . '://' . $digest['host'] . $digest['port'] . $digest['path'] . $extraPath;
			$options = $this->requestOptions($url, $this->config['rTorrentRefresh'], $this->config['rTorrentDisableCertCheck'], $this->config['rTorrentUseCustomCertificate']);
			if ($this->config['rTorrentUsername'] !== '' && $this->decrypt($this->config['rTorrentPassword']) !== '') {
				$credentials = array('auth' => new Requests_Auth_Digest(array($this->config['rTorrentUsername'], $this->decrypt($this->config['rTorrentPassword']))));
				$options = array_merge($options, $credentials);
			}
			$data = xmlrpc_encode_request("d.multicall2", array(
				"",
				"main",
				"d.name=",
				"d.base_path=",
				"d.up.total=",
				"d.size_bytes=",
				"d.down.total=",
				"d.completed_bytes=",
				"d.connection_current=",
				"d.down.rate=",
				"d.up.rate=",
				"d.timestamp.started=",
				"d.state=",
				"d.group.name=",
				"d.hash=",
				"d.complete=",
				"d.ratio=",
				"d.chunk_size=",
				"f.size_bytes=",
				"f.size_chunks=",
				"f.completed_chunks=",
				"d.custom=",
				"d.custom1=",
				"d.custom2=",
				"d.custom3=",
				"d.custom4=",
				"d.custom5=",
			), array());
			$response = Requests::post($url, [], $data, $options);
			if ($response->success) {
				$torrentList = xmlrpc_decode(str_replace('i8>', 'string>', $response->body));
				if (is_array($torrentList)) {
					foreach ($torrentList as $key => $value) {
						$tempStatus = $this->rTorrentStatus($value[13], $value[10], $value[6]);
						if ($tempStatus == 'Seeding' && $this->config['rTorrentHideSeeding']) {
							//do nothing
						} elseif ($tempStatus == 'Finished' && $this->config['rTorrentHideCompleted']) {
							//do nothing
						} else {
							$torrents[$key] = array(
								'name' => $value[0],
								'base' => $value[1],
								'upTotal' => $value[2],
								'size' => $value[3],
								'downTotal' => $value[4],
								'downloaded' => $value[5],
								'connectionState' => $value[6],
								'leech' => $value[7],
								'seed' => $value[8],
								'date' => $value[9],
								'state' => ($value[10]) ? 'on' : 'off',
								'group' => $value[11],
								'hash' => $value[12],
								'complete' => ($value[13]) ? 'yes' : 'no',
								'ratio' => $value[14],
								'label' => $value[20],
								'status' => $tempStatus,
								'temp' => $value[16] . ' - ' . $value[17] . ' - ' . $value[18],
								'custom' => $value[19] . ' - ' . $value[20] . ' - ' . $value[21],
								'custom2' => $value[22] . ' - ' . $value[23] . ' - ' . $value[24],
							);
						}
					}
				}
				if (count($torrents) !== 0) {
					usort($torrents, function ($a, $b) {
						$direction = substr($this->config['rTorrentSortOrder'], -1);
						$sort = substr($this->config['rTorrentSortOrder'], 0, strlen($this->config['rTorrentSortOrder']) - 1);
						switch ($direction) {
							case 'a':
								return $a[$sort] <=> $b[$sort];
								break;
							case 'd':
								return $b[$sort] <=> $a[$sort];
								break;
							default:
								return $b['date'] <=> $a['date'];
						}
					});
					$torrents = array_slice($torrents, 0, $this->config['rTorrentLimit']);
				}
				$api['content']['queueItems'] = $torrents;
				$api['content']['historyItems'] = false;
			}
		} catch
		(Requests_Exception $e) {
			$this->writeLog('error', 'rTorrent Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
			$this->setAPIResponse('error', $e->getMessage(), 500);
			return false;
		};
		$api['content'] = isset($api['content']) ? $api['content'] : false;
		$this->setAPIResponse('success', null, 200, $api);
		return $api;
	}
}