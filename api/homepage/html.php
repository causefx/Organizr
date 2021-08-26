<?php

trait HTMLHomepageItem
{
	public function htmlOneSettingsArray($infoOnly = false)
	{
		$homepageInformation = [
			'name' => 'CustomHTML-1',
			'enabled' => strpos('personal,business', $this->config['license']) !== false,
			'image' => 'plugins/images/tabs/custom1.png',
			'category' => 'Custom',
			'settingsArray' => __FUNCTION__
		];
		if ($infoOnly) {
			return $homepageInformation;
		}
		$homepageSettings = [
			'debug' => true,
			'settings' => [
				'Enable' => [
					$this->settingsOption('enable', 'homepageCustomHTMLoneEnabled'),
					$this->settingsOption('auth', 'homepageCustomHTMLoneAuth'),
				],
				'Code' => [
					$this->settingsOption('pre-code-editor', 'customHTMLone'),
					$this->settingsOption('code-editor', 'customHTMLone'),
				]
			]
		];
		return array_merge($homepageInformation, $homepageSettings);
	}
	
	public function htmlTwoSettingsArray($infoOnly = false)
	{
		$homepageInformation = [
			'name' => 'CustomHTML-2',
			'enabled' => strpos('personal,business', $this->config['license']) !== false,
			'image' => 'plugins/images/tabs/custom2.png',
			'category' => 'Custom',
			'settingsArray' => __FUNCTION__
		];
		if ($infoOnly) {
			return $homepageInformation;
		}
		$homepageSettings = [
			'debug' => true,
			'settings' => [
				'Enable' => [
					$this->settingsOption('enable', 'homepageCustomHTMLtwoEnabled'),
					$this->settingsOption('auth', 'homepageCustomHTMLtwoAuth'),
				],
				'Code' => [
					$this->settingsOption('pre-code-editor', 'customHTMLtwo'),
					$this->settingsOption('code-editor', 'customHTMLtwo'),
				]
			]
		];
		return array_merge($homepageInformation, $homepageSettings);
	}
	
	public function htmlHomepagePermissions($key = null)
	{
		$permissions = [
			'one' => [
				'enabled' => [
					'homepageCustomHTMLoneEnabled'
				],
				'auth' => [
					'homepageCustomHTMLoneAuth'
				],
				'not_empty' => [
					'customHTMLone'
				]
			],
			'two' => [
				'enabled' => [
					'homepageCustomHTMLtwoEnabled'
				],
				'auth' => [
					'homepageCustomHTMLtwoAuth'
				],
				'not_empty' => [
					'customHTMLtwo'
				]
			]
		];
		if (array_key_exists($key, $permissions)) {
			return $permissions[$key];
		} elseif ($key == 'all') {
			return $permissions;
		} else {
			return [];
		}
	}
	
	public function homepageOrdercustomhtml()
	{
		if ($this->homepageItemPermissions($this->htmlHomepagePermissions('one'))) {
			return '
				<div id="' . __FUNCTION__ . '">
					' . $this->config['customHTMLone'] . '
				</div>
				';
		}
	}
	
	public function homepageOrdercustomhtmlTwo()
	{
		if ($this->homepageItemPermissions($this->htmlHomepagePermissions('two'))) {
			return '
				<div id="' . __FUNCTION__ . '">
					' . $this->config['customHTMLtwo'] . '
				</div>
				';
		}
	}
}