<?php

trait OctoPrintHomepageItem
{
	public function getOctoprintHomepageData()
	{
		if (!$this->config['homepageOctoprintEnabled']) {
			$this->setAPIResponse('error', 'OctoPrint homepage item is not enabled', 409);
			return false;
		}
		if (!$this->qualifyRequest($this->config['homepageOctoprintAuth'])) {
			$this->setAPIResponse('error', 'User not approved to view this homepage item', 401);
			return false;
		}
		if (empty($this->config['octoprintURL'])) {
			$this->setAPIResponse('error', 'OctoPrint URL is not defined', 422);
			return false;
		}
		if (empty($this->config['octoprintToken'])) {
			$this->setAPIResponse('error', 'OctoPrint Token is not defined', 422);
			return false;
		}
		$api = [];
		$url = $this->qualifyURL($this->config['octoprintURL']);
		$endpoints = ['job', 'settings'];
		$api['data']['url'] = $this->config['octoprintURL'];
		foreach ($endpoints as $endpoint) {
			$dataUrl = $url . '/api/' . $endpoint;
			try {
				$headers = array('X-API-KEY' => $this->config['octoprintToken']);
				$response = Requests::get($dataUrl, $headers);
				if ($response->success) {
					$json = json_decode($response->body, true);
					$api['data'][$endpoint] = $json;
					$api['options'] = [
						'title' => $this->config['octoprintHeader'],
						'titleToggle' => $this->config['octoprintHeaderToggle'],
					];
				} else {
					$this->setAPIResponse('error', 'OctoPrint connection error', 409);
					return false;
				}
			} catch (Requests_Exception $e) {
				$this->writeLog('error', 'Octoprint Function - Error: ' . $e->getMessage(), 'SYSTEM');
				$this->setAPIResponse('error', $e->getMessage(), 500);
				return false;
			};
		}
		$api = isset($api) ? $api : false;
		$this->setAPIResponse('success', null, 200, $api);
		return $api;
	}
}