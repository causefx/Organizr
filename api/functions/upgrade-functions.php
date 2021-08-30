<?php

trait UpgradeFunctions
{
	public function upgradeToVersion($version = '2.1.0')
	{
		switch ($version) {
			case '2.1.0':
				$this->upgradeSettingsTabURL();
				$this->upgradeHomepageTabURL();
			case '2.1.400':
				$this->removeOldPluginDirectoriesAndFiles();
			case '2.1.525':
				$this->removeOldCustomHTML();
			default:
				$this->setAPIResponse('success', 'Ran update function for version: ' . $version, 200);
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
	
	public function removeOldPluginDirectoriesAndFiles()
	{
		$folders = [
			$this->root . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'api',
			$this->root . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'config',
			$this->root . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'css',
			$this->root . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'js',
			$this->root . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'misc',
		];
		$files = [
			$this->root . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'bookmark.php',
			$this->root . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'chat.php',
			$this->root . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'healthChecks.php',
			$this->root . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'invites.php',
			$this->root . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'php-mailer.php',
			$this->root . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'speedTest.php',
		];
		foreach ($files as $file) {
			if (file_exists($file)) {
				@unlink($file);
			}
		}
		foreach ($folders as $folder) {
			if (file_exists($folder)) {
				@$this->rrmdir($folder);
			}
		}
		return true;
	}
	
	public function removeOldCustomHTML()
	{
		$updateItems = [];
		if ($this->config['homepageCustomHTMLoneEnabled']) {
			if ($this->config['homepageCustomHTMLoneEnabled'] !== '') {
				$updateItemsNew = ['homepageCustomHTML01Enabled' => $this->config['homepageCustomHTMLoneEnabled']];
				$updateItems = array_merge($updateItems, $updateItemsNew);
			}
		}
		if ($this->config['homepageCustomHTMLoneAuth']) {
			if ($this->config['homepageCustomHTMLoneAuth'] !== '') {
				$updateItemsNew = ['homepageCustomHTML01Auth' => $this->config['homepageCustomHTMLoneAuth']];
				$updateItems = array_merge($updateItems, $updateItemsNew);
			}
		}
		if ($this->config['customHTMLone']) {
			if ($this->config['customHTMLone'] !== '') {
				$updateItemsNew = ['customHTML01' => $this->config['customHTMLone']];
				$updateItems = array_merge($updateItems, $updateItemsNew);
			}
		}
		if ($this->config['homepageCustomHTMLtwoEnabled']) {
			if ($this->config['homepageCustomHTMLtwoEnabled'] !== '') {
				$updateItemsNew = ['homepageCustomHTML02Enabled' => $this->config['homepageCustomHTMLtwoEnabled']];
				$updateItems = array_merge($updateItems, $updateItemsNew);
			}
		}
		if ($this->config['homepageCustomHTMLtwoAuth']) {
			if ($this->config['homepageCustomHTMLtwoAuth'] !== '') {
				$updateItemsNew = ['homepageCustomHTML02Auth' => $this->config['homepageCustomHTMLtwoAuth']];
				$updateItems = array_merge($updateItems, $updateItemsNew);
			}
		}
		if ($this->config['customHTMLtwo']) {
			if ($this->config['customHTMLtwo'] !== '') {
				$updateItemsNew = ['customHTML02' => $this->config['customHTMLtwo']];
				$updateItems = array_merge($updateItems, $updateItemsNew);
			}
		}
		if (!empty($updateItems)) {
			$this->updateConfig($updateItems);
		}
		$this->removeConfigItem(['homepageOrdercustomhtml', 'homepageOrdercustomhtmlTwo', 'homepageCustomHTMLoneEnabled', 'homepageCustomHTMLoneAuth', 'customHTMLone', 'homepageCustomHTMLtwoEnabled', 'homepageCustomHTMLtwoAuth', 'customHTMLtwo']);
		return true;
	}
}