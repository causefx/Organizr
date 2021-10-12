<?php
// PLUGIN INFORMATION
$GLOBALS['plugins']['Test'] = array( // Plugin Name
	'name' => 'Test', // Plugin Name
	'author' => 'CauseFX', // Who wrote the plugin
	'category' => 'Testing', // One to Two Word Description
	'link' => '', // Link to plugin info
	'license' => 'personal', // License Type use , for multiple
	'idPrefix' => 'TEST', // html element id prefix (All Uppercase)
	'configPrefix' => 'TEST', // config file prefix for array items without the hypen (All Uppercase)
	'version' => '1.0.1', // SemVer of plugin
	'image' => 'api/plugins/test/logo.png', // 1:1 non transparent image for plugin
	'settings' => true, // does plugin need a settings modal?
	'bind' => true, // use default bind to make settings page - true or false
	'api' => 'api/v2/plugins/test/settings', // api route for settings page (All Lowercase)
	'homepage' => false // Is plugin for use on homepage? true or false
);

class TestPlugin extends Organizr
{
	public function _pluginGetSettings()
	{
		return array(
			'Sample Settings' => array(
				array(
					'type' => 'password-alt',
					'name' => 'TEST-pass-alt',
					'label' => 'Test Plugin Pass Alt',
					'value' => $this->config['TEST-pass-alt'],
				),
				array(
					'type' => 'password',
					'name' => 'TEST-pass',
					'label' => 'Test Plugin Password',
					'value' => $this->config['TEST-pass'],
				),
				array(
					'type' => 'text',
					'name' => 'TEST-text',
					'label' => 'Test Plugin Text',
					'value' => $this->config['TEST-text'],
					'placeholder' => 'All'
				),
			),
			'FYI' => array(
				array(
					'type' => 'html',
					'label' => 'Note',
					'html' => 'just a note...'
				)
			)
		);
	}
}
