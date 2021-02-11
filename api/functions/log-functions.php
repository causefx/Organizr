<?php

trait LogFunctions
{
	public function info($msg, $username = null)
	{
		$this->writeLog('info', $msg, $username);
	}
	
	public function error($msg, $username = null)
	{
		$this->writeLog('error', $msg, $username);
	}
	
	public function warning($msg, $username = null)
	{
		$this->writeLog('warning', $msg, $username);
	}
	
	public function debug($msg, $username = null)
	{
		$this->writeLog('debug', $msg, $username);
	}
}