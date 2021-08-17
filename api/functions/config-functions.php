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
}