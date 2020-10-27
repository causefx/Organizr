<?php

trait UpgradeFunctions
{
	public function upgradeToVersion($version = '2.1.0')
	{
		switch ($version) {
			case '2.1.0':
				$this->upgradeSettingsTabURL();
				$this->upgradeHomepageTabURL();
				$this->setAPIResponse('success', 'Ran update function for version: ' . $version, 200);
				return true;
			default:
				return true;
		}
	}
	
	public function upgradeSettingsTabURL()
	{
		$response = [
			array(
				'function' => 'query',
				'query' => array(
					'UPDATE tabs SET',
					['url' => 'api/v2/page/settings'],
					'WHERE url = ?',
					'api/?v1/settings/page'
				)
			),
		];
		return $this->processQueries($response);
	}
	
	public function upgradeHomepageTabURL()
	{
		$response = [
			array(
				'function' => 'query',
				'query' => array(
					'UPDATE tabs SET',
					['url' => 'api/v2/page/homepage'],
					'WHERE url = ?',
					'api/?v1/homepage/page'
				)
			),
		];
		return $this->processQueries($response);
	}
}