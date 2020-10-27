<?php
// PLUGIN INFORMATION
$GLOBALS['plugins'][]['SpeedTest'] = array( // Plugin Name
	'name' => 'SpeedTest', // Plugin Name
	'author' => 'CauseFX', // Who wrote the plugin
	'category' => 'Utilities', // One to Two Word Description
	'link' => 'https://github.com/PHPMailer/PHPMailer', // Link to plugin info
	'license' => 'personal,business', // License Type use , for multiple
	'idPrefix' => 'SPEEDTEST', // html element id prefix
	'configPrefix' => 'SPEEDTEST', // config file prefix for array items without the hypen
	'version' => '1.0.0', // SemVer of plugin
	'image' => 'plugins/images/speedtest.png', // 1:1 non transparent image for plugin
	'settings' => true, // does plugin need a settings page? true or false
	'homepage' => false // Is plugin for use on homepage? true or false
);

class SpeedTest extends Organizr
{
	public function speedTestGetSettings()
	{
		return array(
			'Options' => array(
				array(
					'type' => 'select',
					'name' => 'SPEEDTEST-Auth-include',
					'label' => 'Minimum Authentication',
					'value' => $this->config['SPEEDTEST-Auth-include'],
					'options' => $this->groupSelect()
				)
			)
		);
	}
}
