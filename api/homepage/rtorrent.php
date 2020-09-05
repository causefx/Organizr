<?php

trait RTorrentHomepageItem
{
	public function rTorrentSettingsArray()
	{
		$xmlStatus = (extension_loaded('xmlrpc')) ? 'Installed' : 'Not Installed';
		return array(
			'name' => 'rTorrent',
			'enabled' => strpos('personal', $this->config['license']) !== false,
			'image' => 'plugins/images/tabs/rTorrent.png',
			'category' => 'Downloader',
			'settings' => array(
				'FYI' => array(
					array(
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
					)
				),
				'Enable' => array(
					array(
						'type' => 'switch',
						'name' => 'homepagerTorrentEnabled',
						'label' => 'Enable',
						'value' => $this->config['homepagerTorrentEnabled']
					),
					array(
						'type' => 'select',
						'name' => 'homepagerTorrentAuth',
						'label' => 'Minimum Authentication',
						'value' => $this->config['homepagerTorrentAuth'],
						'options' => $this->groupOptions
					)
				),
				'Connection' => array(
					array(
						'type' => 'input',
						'name' => 'rTorrentURL',
						'label' => 'URL',
						'value' => $this->config['rTorrentURL'],
						'help' => 'Please make sure to use local IP address and port - You also may use local dns name too.',
						'placeholder' => 'http(s)://hostname:port'
					),
					array(
						'type' => 'input',
						'name' => 'rTorrentURLOverride',
						'label' => 'rTorrent API URL Override',
						'value' => $this->config['rTorrentURLOverride'],
						'help' => 'Only use if you cannot connect.  Please make sure to use local IP address and port - You also may use local dns name too.',
						'placeholder' => 'http(s)://hostname:port/xmlrpc'
					),
					array(
						'type' => 'input',
						'name' => 'rTorrentUsername',
						'label' => 'Username',
						'value' => $this->config['rTorrentUsername']
					),
					array(
						'type' => 'password',
						'name' => 'rTorrentPassword',
						'label' => 'Password',
						'value' => $this->config['rTorrentPassword']
					),
					array(
						'type' => 'switch',
						'name' => 'rTorrentDisableCertCheck',
						'label' => 'Disable Certificate Check',
						'value' => $this->config['rTorrentDisableCertCheck']
					),
				),
				'Misc Options' => array(
					array(
						'type' => 'switch',
						'name' => 'rTorrentHideSeeding',
						'label' => 'Hide Seeding',
						'value' => $this->config['rTorrentHideSeeding']
					), array(
						'type' => 'switch',
						'name' => 'rTorrentHideCompleted',
						'label' => 'Hide Completed',
						'value' => $this->config['rTorrentHideCompleted']
					),
					array(
						'type' => 'select',
						'name' => 'rTorrentSortOrder',
						'label' => 'Order',
						'value' => $this->config['rTorrentSortOrder'],
						'options' => $this->rTorrentSortOptions()
					),
					array(
						'type' => 'select',
						'name' => 'homepageDownloadRefresh',
						'label' => 'Refresh Seconds',
						'value' => $this->config['homepageDownloadRefresh'],
						'options' => $this->timeOptions()
					),
					array(
						'type' => 'number',
						'name' => 'rTorrentLimit',
						'label' => 'Item Limit',
						'value' => $this->config['rTorrentLimit'],
					),
					array(
						'type' => 'switch',
						'name' => 'rTorrentCombine',
						'label' => 'Add to Combined Downloader',
						'value' => $this->config['rTorrentCombine']
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
						'attr' => 'onclick="testAPIConnection(\'rtorrent\')"'
					),
				)
			)
		);
	}
	
	public function testConnectionRTorrent()
	{
		if (empty($this->config['rTorrentURL']) && empty($this->config['rTorrentURLOverride'])) {
			$this->setAPIResponse('error', 'rTorrent URL is not defined', 422);
			return false;
		}
		try {
			$digest = (empty($this->config['rTorrentURLOverride'])) ? $this->qualifyURL($this->config['rTorrentURL'], true) : $this->qualifyURL($this->checkOverrideURL($this->config['rTorrentURL'], $this->config['rTorrentURLOverride']), true);
			$passwordInclude = ($this->config['rTorrentUsername'] !== '' && $this->config['rTorrentPassword'] !== '') ? $this->config['rTorrentUsername'] . ':' . $this->decrypt($this->config['rTorrentPassword']) . "@" : '';
			$extraPath = (strpos($this->config['rTorrentURL'], '.php') !== false) ? '' : '/RPC2';
			$extraPath = (empty($this->config['rTorrentURLOverride'])) ? $extraPath : '';
			$url = $digest['scheme'] . '://' . $passwordInclude . $digest['host'] . $digest['port'] . $digest['path'] . $extraPath;
			$options = ($this->localURL($url, $this->config['rTorrentDisableCertCheck'])) ? array('verify' => false) : array();
			if ($this->config['rTorrentUsername'] !== '' && $this->decrypt($this->config['rTorrentPassword']) !== '') {
				$credentials = array('auth' => new Requests_Auth_Digest(array($this->config['rTorrentUsername'], $this->decrypt($this->config['rTorrentPassword']))));
				$options = array_merge($options, $credentials);
			}
			$data = xmlrpc_encode_request("system.listMethods", null);
			$response = Requests::post($url, array(), $data, $options);
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
		if (!$this->config['homepagerTorrentEnabled']) {
			$this->setAPIResponse('error', 'rTorrent homepage item is not enabled', 409);
			return false;
		}
		if (!$this->qualifyRequest($this->config['homepagerTorrentAuth'])) {
			$this->setAPIResponse('error', 'User not approved to view this homepage item', 401);
			return false;
		}
		if (empty($this->config['rTorrentURL']) && empty($this->config['rTorrentURLOverride'])) {
			$this->setAPIResponse('error', 'rTorrent URL is not defined', 422);
			return false;
		}
		try {
			if ($this->config['rTorrentLimit'] == '0') {
				$this->config['rTorrentLimit'] = '1000';
			}
			$torrents = array();
			$digest = (empty($this->config['rTorrentURLOverride'])) ? $this->qualifyURL($this->config['rTorrentURL'], true) : $this->qualifyURL($this->checkOverrideURL($this->config['rTorrentURL'], $this->config['rTorrentURLOverride']), true);
			$passwordInclude = ($this->config['rTorrentUsername'] !== '' && $this->config['rTorrentPassword'] !== '') ? $this->config['rTorrentUsername'] . ':' . $this->decrypt($this->config['rTorrentPassword']) . "@" : '';
			$extraPath = (strpos($this->config['rTorrentURL'], '.php') !== false) ? '' : '/RPC2';
			$extraPath = (empty($this->config['rTorrentURLOverride'])) ? $extraPath : '';
			$url = $digest['scheme'] . '://' . $passwordInclude . $digest['host'] . $digest['port'] . $digest['path'] . $extraPath;
			$options = (localURL($url, $this->config['rTorrentDisableCertCheck'])) ? array('verify' => false) : array();
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
			$response = Requests::post($url, array(), $data, $options);
			if ($response->success) {
				$torrentList = xmlrpc_decode(str_replace('i8>', 'string>', $response->body));
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