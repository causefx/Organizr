<?php

trait NZBGetHomepageItem
{
	public function testConnectionNZBGet()
	{
		if (empty($this->config['nzbgetURL'])) {
			$this->setAPIResponse('error', 'NZBGet URL is not defined', 422);
			return false;
		}
		try {
			$url = $this->qualifyURL($this->config['nzbgetURL']);
			$options = ($this->localURL($url)) ? array('verify' => false) : array();
			$urlGroups = $url . '/jsonrpc/listgroups';
			if ($this->config['nzbgetUsername'] !== '' && $this->decrypt($this->config['nzbgetPassword']) !== '') {
				$credentials = array('auth' => new Requests_Auth_Basic(array($this->config['nzbgetUsername'], $this->decrypt($this->config['nzbgetPassword']))));
				$options = array_merge($options, $credentials);
			}
			$response = Requests::get($urlGroups, array(), $options);
			if ($response->success) {
				$this->setAPIResponse('success', 'API Connection succeeded', 200);
				return true;
			} else {
				$this->setAPIResponse('success', 'NZBGet: An Error Occurred', 500);
				return false;
			}
		} catch (Requests_Exception $e) {
			$this->writeLog('error', 'NZBGet Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
			$this->setAPIResponse('error', $e->getMessage(), 500);
			return false;
		}
	}
	
	public function getNzbgetHomepageQueue()
	{
		if (!$this->config['homepageNzbgetEnabled']) {
			$this->setAPIResponse('error', 'NZBGet homepage item is not enabled', 409);
			return false;
		}
		if (!$this->qualifyRequest($this->config['homepageNzbgetAuth'])) {
			$this->setAPIResponse('error', 'User not approved to view this homepage item', 401);
			return false;
		}
		if (empty($this->config['nzbgetURL'])) {
			$this->setAPIResponse('error', 'NZBGet URL is not defined', 422);
			return false;
		}
		try {
			$url = $this->qualifyURL($this->config['nzbgetURL']);
			$options = ($this->localURL($url)) ? array('verify' => false) : array();
			$urlGroups = $url . '/jsonrpc/listgroups';
			$urlHistory = $url . '/jsonrpc/history';
			if ($this->config['nzbgetUsername'] !== '' && $this->decrypt($this->config['nzbgetPassword']) !== '') {
				$credentials = array('auth' => new Requests_Auth_Basic(array($this->config['nzbgetUsername'], $this->decrypt($this->config['nzbgetPassword']))));
				$options = array_merge($options, $credentials);
			}
			$response = Requests::get($urlGroups, array(), $options);
			if ($response->success) {
				$api['content']['queueItems'] = json_decode($response->body, true);
			}
			$response = Requests::get($urlHistory, array(), $options);
			if ($response->success) {
				$api['content']['historyItems'] = json_decode($response->body, true);
			}
			$api['content'] = isset($api['content']) ? $api['content'] : false;
			$this->setAPIResponse('success', null, 200, $api);
			return $api;
		} catch (Requests_Exception $e) {
			$this->writeLog('error', 'NZBGet Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
			$this->setAPIResponse('error', $e->getMessage(), 500);
			return false;
		}
	}
}