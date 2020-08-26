<?php

trait PiHoleHomepageItem
{
	public function testConnectionPihole()
	{
		if (empty($this->config['piholeURL'])) {
			$this->setAPIResponse('error', 'Pihole URL is not defined', 422);
			return false;
		}
		$api = array();
		$failed = false;
		$errors = '';
		$urls = explode(',', $this->config['piholeURL']);
		foreach ($urls as $url) {
			$url = $url . '/api.php?';
			try {
				$response = Requests::get($url, [], []);
				if ($response->success) {
					@$test = json_decode($response->body, true);
					if (!is_array($test)) {
						$ip = $this->qualifyURL($url, true)['host'];
						$errors .= $ip . ': Response was not JSON';
						$failed = true;
					}
				}
				if (!$response->success) {
					$ip = $this->qualifyURL($url, true)['host'];
					$errors .= $ip . ': Unknown Failure';
					$failed = true;
				}
			} catch (Requests_Exception $e) {
				$failed = true;
				$ip = $this->qualifyURL($url, true)['host'];
				$errors .= $ip . ': ' . $e->getMessage();
				$this->writeLog('error', 'Pi-hole Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
			};
		}
		if ($failed) {
			$this->setAPIResponse('error', $errors, 500);
			return false;
		} else {
			$this->setAPIResponse('success', null, 200);
			return true;
		}
	}
	
	public function getPiholeHomepageStats()
	{
		if (!$this->config['homepagePiholeEnabled']) {
			$this->setAPIResponse('error', 'Pihole homepage item is not enabled', 409);
			return false;
		}
		if (!$this->qualifyRequest($this->config['homepagePiholeAuth'])) {
			$this->setAPIResponse('error', 'User not approved to view this homepage item', 401);
			return false;
		}
		if (empty($this->config['piholeURL'])) {
			$this->setAPIResponse('error', 'Pihole URL is not defined', 422);
			return false;
		}
		$api = array();
		$urls = explode(',', $this->config['piholeURL']);
		foreach ($urls as $url) {
			$url = $url . '/api.php?';
			try {
				$response = Requests::get($url, [], []);
				if ($response->success) {
					@$piholeResults = json_decode($response->body, true);
					if (is_array($piholeResults)) {
						$ip = $this->qualifyURL($url, true)['host'];
						$api['data'][$ip] = $piholeResults;
					}
				}
			} catch (Requests_Exception $e) {
				$this->setAPIResponse('error', $e->getMessage(), 500);
				$this->writeLog('error', 'Pi-hole Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
				return false;
			};
		}
		$api['options']['combine'] = $this->config['homepagePiholeCombine'];
		$api['options']['title'] = $this->config['piholeHeaderToggle'];
		$api = isset($api) ? $api : null;
		$this->setAPIResponse('success', null, 200, $api);
		return $api;
	}
}