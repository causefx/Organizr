<?php

trait JDownloaderHomepageItem
{
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