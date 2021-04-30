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
		$homepageSettings = array(
			'settings' => array(
				'Enable' => array(
					array(
						'type' => 'switch',
						'name' => 'homepageCustomHTMLoneEnabled',
						'label' => 'Enable',
						'value' => $this->config['homepageCustomHTMLoneEnabled']
					),
					array(
						'type' => 'select',
						'name' => 'homepageCustomHTMLoneAuth',
						'label' => 'Minimum Authentication',
						'value' => $this->config['homepageCustomHTMLoneAuth'],
						'options' => $this->groupOptions
					)
				),
				'Code' => array(
					array(
						'type' => 'textbox',
						'name' => 'customHTMLone',
						'class' => 'hidden customHTMLoneTextarea',
						'label' => '',
						'value' => $this->config['customHTMLone'],
					),
					array(
						'type' => 'html',
						'override' => 12,
						'label' => 'Custom HTML/JavaScript',
						'html' => '<button type="button" class="hidden savecustomHTMLoneTextarea btn btn-info btn-circle pull-right m-r-5 m-l-10"><i class="fa fa-save"></i> </button><div id="customHTMLoneEditor" style="height:300px">' . htmlentities($this->config['customHTMLone']) . '</div>'
					),
				)
			)
		);
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
		$homepageSettings = array(
			'name' => 'CustomHTML-2',
			'enabled' => strpos('personal,business', $this->config['license']) !== false,
			'image' => 'plugins/images/tabs/custom2.png',
			'category' => 'Custom',
			'settings' => array(
				'Enable' => array(
					array(
						'type' => 'switch',
						'name' => 'homepageCustomHTMLtwoEnabled',
						'label' => 'Enable',
						'value' => $this->config['homepageCustomHTMLtwoEnabled']
					),
					array(
						'type' => 'select',
						'name' => 'homepageCustomHTMLtwoAuth',
						'label' => 'Minimum Authentication',
						'value' => $this->config['homepageCustomHTMLtwoAuth'],
						'options' => $this->groupOptions
					)
				),
				'Code' => array(
					array(
						'type' => 'textbox',
						'name' => 'customHTMLtwo',
						'class' => 'hidden customHTMLtwoTextarea',
						'label' => '',
						'value' => $this->config['customHTMLtwo'],
					),
					array(
						'type' => 'html',
						'override' => 12,
						'label' => 'Custom HTML/JavaScript',
						'html' => '<button type="button" class="hidden savecustomHTMLtwoTextarea btn btn-info btn-circle pull-right m-r-5 m-l-10"><i class="fa fa-save"></i> </button><div id="customHTMLtwoEditor" style="height:300px">' . htmlentities($this->config['customHTMLtwo']) . '</div>'
					),
				)
			)
		);
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