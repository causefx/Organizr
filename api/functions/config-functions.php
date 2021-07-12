<?php

trait ConfigFunctions
{
	public function getConfigItem($item)
	{
		if ($this->config[$item]) {
			$this->setAPIResponse('success', null, 200, $this->config[$item]);
			return $this->config[$item];
		} else {
			$this->setAPIResponse('error', '${$this->config[$item]} is not defined', 404);
			return false;
		}
		
	}
}