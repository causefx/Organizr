<?php

trait HTMLHomepageItem
{
	public function customHtmlNumber()
	{
		return 8;
	}
	
	public function customHtmlSettingsArray($infoOnly = false)
	{
		$homepageInformation = [
			'name' => 'CustomHTML',
			'enabled' => strpos('personal,business', $this->config['license']) !== false,
			'image' => 'plugins/images/tabs/HTML5.png',
			'category' => 'Custom',
			'settingsArray' => __FUNCTION__
		];
		if ($infoOnly) {
			return $homepageInformation;
		}
		$homepageSettings = [
			'debug' => true,
			'settings' => []
		];
		for ($i = 1; $i <= $this->customHtmlNumber(); $i++) {
			$i = sprintf('%02d', $i);
			$homepageSettings['settings']['Custom HTML ' . $i] = array(
				$this->settingsOption('enable', 'homepageCustomHTML' . $i . 'Enabled'),
				$this->settingsOption('auth', 'homepageCustomHTML' . $i . 'Auth'),
				//$this->settingsOption('pre-code-editor', 'customHTML' . $i), // possibly can remove this as we consolidated the type into one
				$this->settingsOption('code-editor', 'customHTML' . $i, ['label' => 'Custom HTML Code', 'mode' => 'html']),
			);
		}
		return array_merge($homepageInformation, $homepageSettings);
	}
	
	public function htmlHomepagePermissions($key = null)
	{
		for ($i = 1; $i <= $this->customHtmlNumber(); $i++) {
			$i = sprintf('%02d', $i);
			$permissions[$i] = [
				'enabled' => [
					'homepageCustomHTML' . $i . 'Enabled'
				],
				'auth' => [
					'homepageCustomHTML' . $i . 'Auth'
				],
				'not_empty' => [
					'customHTML' . $i
				]
			];
		}
		return $this->homepageCheckKeyPermissions($key, $permissions);
	}
	
	public function homepageOrdercustomhtml($key = '01')
	{
		if ($this->homepageItemPermissions($this->htmlHomepagePermissions($key))) {
			return '
				<div id="' . __FUNCTION__ . '">
					' . $this->config['customHTML' . $key] . '
				</div>
				';
		}
	}
}