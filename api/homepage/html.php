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
				$this->settingsOption('pre-code-editor', 'customHTML' . $i),
				$this->settingsOption('code-editor', 'customHTML' . $i, ['label' => 'Custom HTML Code']),
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
		if (array_key_exists($key, $permissions)) {
			return $permissions[$key];
		} elseif ($key == 'all') {
			return $permissions;
		} else {
			return [];
		}
	}
	
	public function homepageOrdercustomhtml01()
	{
		if ($this->homepageItemPermissions($this->htmlHomepagePermissions('01'))) {
			return '
				<div id="' . __FUNCTION__ . '">
					' . $this->config['customHTML01'] . '
				</div>
				';
		}
	}
	
	public function homepageOrdercustomhtml02()
	{
		if ($this->homepageItemPermissions($this->htmlHomepagePermissions('02'))) {
			return '
				<div id="' . __FUNCTION__ . '">
					' . $this->config['customHTML02'] . '
				</div>
				';
		}
	}
	
	public function homepageOrdercustomhtml03()
	{
		if ($this->homepageItemPermissions($this->htmlHomepagePermissions('03'))) {
			return '
				<div id="' . __FUNCTION__ . '">
					' . $this->config['customHTML03'] . '
				</div>
				';
		}
	}
	
	public function homepageOrdercustomhtml04()
	{
		if ($this->homepageItemPermissions($this->htmlHomepagePermissions('04'))) {
			return '
				<div id="' . __FUNCTION__ . '">
					' . $this->config['customHTML04'] . '
				</div>
				';
		}
	}
	
	public function homepageOrdercustomhtml05()
	{
		if ($this->homepageItemPermissions($this->htmlHomepagePermissions('05'))) {
			return '
				<div id="' . __FUNCTION__ . '">
					' . $this->config['customHTML05'] . '
				</div>
				';
		}
	}
	
	public function homepageOrdercustomhtml06()
	{
		if ($this->homepageItemPermissions($this->htmlHomepagePermissions('06'))) {
			return '
				<div id="' . __FUNCTION__ . '">
					' . $this->config['customHTML06'] . '
				</div>
				';
		}
	}
	
	public function homepageOrdercustomhtml07()
	{
		if ($this->homepageItemPermissions($this->htmlHomepagePermissions('07'))) {
			return '
				<div id="' . __FUNCTION__ . '">
					' . $this->config['customHTML07'] . '
				</div>
				';
		}
	}
	
	public function homepageOrdercustomhtml08()
	{
		if ($this->homepageItemPermissions($this->htmlHomepagePermissions('08'))) {
			return '
				<div id="' . __FUNCTION__ . '">
					' . $this->config['customHTML08'] . '
				</div>
				';
		}
	}
}