<?php

trait HomepageFunctions
{
	public function getHomepageSettingsList()
	{
		$methods = get_class_methods($this);
		$searchTerm = 'SettingsArray';
		return array_filter($methods, function ($k) use ($searchTerm) {
			return stripos($k, $searchTerm) !== false;
		}, 0);
	}
	
	public function getHomepageSettingsCombined()
	{
		$list = $this->getHomepageSettingsList();
		$combined = [];
		foreach ($list as $item) {
			$combined[] = $this->$item();
		}
		return $combined;
	}
	
	public function homepageItemPermissions($settings = false, $api = false)
	{
		if (!$settings) {
			if ($api) {
				$this->setAPIResponse('error', 'No settings were supplied', 422);
			}
			return false;
		}
		foreach ($settings as $type => $setting) {
			$settingsType = gettype($setting);
			switch ($type) {
				case 'enabled':
					if ($settingsType == 'string') {
						if (!$this->config[$setting]) {
							if ($api) {
								$this->setAPIResponse('error', $setting . ' module is not enabled', 409);
							}
							return false;
						}
					} else {
						foreach ($setting as $item) {
							if (!$this->config[$item]) {
								if ($api) {
									$this->setAPIResponse('error', $item . ' module is not enabled', 409);
								}
								return false;
							}
						}
					}
					break;
				case 'auth':
					if ($settingsType == 'string') {
						if (!$this->qualifyRequest($this->config[$setting])) {
							if ($api) {
								$this->setAPIResponse('error', 'User not approved to view this homepage item', 401);
							}
							return false;
						}
					} else {
						foreach ($setting as $item) {
							if (!$this->qualifyRequest($this->config[$item])) {
								if ($api) {
									$this->setAPIResponse('error', 'User not approved to view this homepage item', 401);
								}
								return false;
							}
						}
					}
					break;
				case 'not_empty':
					if ($settingsType == 'string') {
						if (empty($this->config[$setting])) {
							if ($api) {
								$this->setAPIResponse('error', $setting . 'was not supplied', 422);
							}
							return false;
						}
					} else {
						foreach ($setting as $item) {
							if (empty($this->config[$item])) {
								if ($api) {
									$this->setAPIResponse('error', $item . 'was not supplied', 422);
								}
								return false;
							}
						}
					}
					break;
				default:
					//return false;
			}
		}
		return true;
	}
}
