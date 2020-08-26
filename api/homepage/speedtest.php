<?php

trait SpeedTestHomepageItem
{
	public function getSpeedtestHomepageData()
	{
		if (!$this->config['homepageSpeedtestEnabled']) {
			$this->setAPIResponse('error', 'SpeedTest homepage item is not enabled', 409);
			return false;
		}
		if (!$this->qualifyRequest($this->config['homepageSpeedtestAuth'])) {
			$this->setAPIResponse('error', 'User not approved to view this homepage item', 401);
			return false;
		}
		if (empty($this->config['speedtestURL'])) {
			$this->setAPIResponse('error', 'SpeedTest URL is not defined', 422);
			return false;
		}
		$api = [];
		$url = $this->qualifyURL($this->config['speedtestURL']);
		$dataUrl = $url . '/api/speedtest/latest';
		try {
			$response = Requests::get($dataUrl);
			if ($response->success) {
				$json = json_decode($response->body, true);
				$api['data'] = [
					'current' => $json['data'],
					'average' => $json['average'],
					'max' => $json['max'],
				];
				$api['options'] = [
					'title' => $this->config['speedtestHeader'],
					'titleToggle' => $this->config['speedtestHeaderToggle'],
				];
			} else {
				$this->setAPIResponse('error', 'SpeedTest connection error', 409);
				return false;
			}
		} catch (Requests_Exception $e) {
			$this->writeLog('error', 'Speedtest Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
			$this->setAPIResponse('error', $e->getMessage(), 500);
			return false;
		};
		$api = isset($api) ? $api : false;
		$this->setAPIResponse('success', null, 200, $api);
		return $api;
	}
}