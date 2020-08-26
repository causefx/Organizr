<?php

trait DelugeHomepageItem
{
	public function testConnectionDeluge()
	{
		if (empty($this->config['delugeURL'])) {
			$this->setAPIResponse('error', 'Deluge URL is not defined', 422);
			return false;
		}
		if (empty($this->config['delugePassword'])) {
			$this->setAPIResponse('error', 'Deluge Password is not defined', 422);
			return false;
		}
		try {
			$deluge = new deluge($this->config['delugeURL'], $this->decrypt($this->config['delugePassword']));
			$torrents = $deluge->getTorrents(null, 'comment, download_payload_rate, eta, hash, is_finished, is_seed, message, name, paused, progress, queue, state, total_size, upload_payload_rate');
			$this->setAPIResponse('success', 'API Connection succeeded', 200);
			return true;
		} catch (Exception $e) {
			$this->writeLog('error', 'NZBGet Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
			$this->setAPIResponse('error', $e->getMessage(), 500);
			return false;
		}
		
	}
	
	public function delugeStatus($queued, $status, $state)
	{
		if ($queued == '-1' && $state == '100' && ($status == 'Seeding' || $status == 'Queued' || $status == 'Paused')) {
			$state = 'Seeding';
		} elseif ($state !== '100') {
			$state = 'Downloading';
		} else {
			$state = 'Finished';
		}
		return ($state) ? $state : $status;
	}
	
	public function getDelugeHomepageQueue()
	{
		if (!$this->config['homepageDelugeEnabled']) {
			$this->setAPIResponse('error', 'Deluge homepage item is not enabled', 409);
			return false;
		}
		if (!$this->qualifyRequest($this->config['homepageDelugeAuth'])) {
			$this->setAPIResponse('error', 'User not approved to view this homepage item', 401);
			return false;
		}
		if (empty($this->config['delugeURL'])) {
			$this->setAPIResponse('error', 'Deluge URL is not defined', 422);
			return false;
		}
		if (empty($this->config['delugePassword'])) {
			$this->setAPIResponse('error', 'Deluge Password is not defined', 422);
			return false;
		}
		try {
			$deluge = new deluge($this->config['delugeURL'], $this->decrypt($this->config['delugePassword']));
			$torrents = $deluge->getTorrents(null, 'comment, download_payload_rate, eta, hash, is_finished, is_seed, message, name, paused, progress, queue, state, total_size, upload_payload_rate');
			foreach ($torrents as $key => $value) {
				$tempStatus = $this->delugeStatus($value->queue, $value->state, $value->progress);
				if ($tempStatus == 'Seeding' && $this->config['delugeHideSeeding']) {
					//do nothing
				} elseif ($tempStatus == 'Finished' && $this->config['delugeHideCompleted']) {
					//do nothing
				} else {
					$api['content']['queueItems'][] = $value;
				}
			}
			$api['content']['queueItems'] = (empty($api['content']['queueItems'])) ? [] : $api['content']['queueItems'];
			$api['content']['historyItems'] = false;
		} catch (Excecption $e) {
			$this->writeLog('error', 'Deluge Connect Function - Error: ' . $e->getMessage(), 'SYSTEM');
			$this->setAPIResponse('error', $e->getMessage(), 500);
			return false;
		}
		$api['content'] = isset($api['content']) ? $api['content'] : false;
		$this->setAPIResponse('success', null, 200, $api);
		return $api;
	}
}