<?php

trait JDownloaderHomepageItem
{
	public function jDownloaderSettingsArray()
	{
		return array(
			'name' => 'JDownloader',
			'enabled' => strpos('personal', $this->config['license']) !== false,
			'image' => 'plugins/images/tabs/jdownloader.png',
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
                                        <li><i class="fa fa-chevron-right text-danger"></i> <a href="https://pypi.org/project/myjd-api/" target="_blank">Download [myjd-api] Module</a></li>
                                        <li><i class="fa fa-chevron-right text-danger"></i> Add <b>/api/myjd</b> to the URL if you are using <a href="https://pypi.org/project/RSScrawler/" target="_blank">RSScrawler</a></li>
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
						'name' => 'homepageDownloadRefresh',
						'label' => 'Refresh Seconds',
						'value' => $this->config['homepageDownloadRefresh'],
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
	}
	
	public function testConnectionJDownloader()
	{
		if (empty($this->config['jdownloaderURL'])) {
			$this->setAPIResponse('error', 'JDownloader URL is not defined', 422);
			return false;
		}
		$url = $this->qualifyURL($this->config['jdownloaderURL']);
		try {
			$options = ($this->localURL($url)) ? array('verify' => false, 'timeout' => 30) : array('timeout' => 30);
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
	
	public function getJdownloaderHomepageQueue()
	{
		if (!$this->config['homepageJdownloaderEnabled']) {
			$this->setAPIResponse('error', 'JDownloader homepage item is not enabled', 409);
			return false;
		}
		if (!$this->qualifyRequest($this->config['homepageJdownloaderAuth'])) {
			$this->setAPIResponse('error', 'User not approved to view this homepage item', 401);
			return false;
		}
		if (empty($this->config['jdownloaderURL'])) {
			$this->setAPIResponse('error', 'JDownloader URL is not defined', 422);
			return false;
		}
		$url = $this->qualifyURL($this->config['jdownloaderURL']);
		try {
			$options = ($this->localURL($url)) ? array('verify' => false, 'timeout' => 30) : array('timeout' => 30);
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