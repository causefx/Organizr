<?php

trait ConfigFunctions
{
	public function getConfigItem($item)
	{
		if ($this->config[$item]) {
			$configItem = $this->config[$item];
			if ($item == 'organizrHash') {
				$configItem = '***Secure***';
			}
			$this->setAPIResponse('success', null, 200, $configItem);
			return $this->config[$item];
		} else {
			$this->setAPIResponse('error', $item . ' is not defined or is blank', 404);
			return false;
		}
	}
	
	public function getConfigItems()
	{
		$configItems = $this->config;
		/*
		foreach ($configItems as $configItem => $configItemValue) {
			// should we keep this to filter more items?
			if ($configItem == 'organizrHash') {
				$configItems[$configItem] = '***Secure***';
			}
		}
		*/
		$configItems['organizrHash'] = '***Secure***';
		$this->setAPIResponse('success', null, 200, $configItems);
		return $configItems;
		
	}
}