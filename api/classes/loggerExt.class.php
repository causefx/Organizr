<?php

use Nekonomokochan\PhpJsonLogger\Logger;

class OrganizrLoggerExt extends Logger
{
	/**
	 * @param $message
	 * @param $context
	 */
	public function debug($message, $context = '')
	{
		$context = $this->formatParamToArray($context);
		$this->addDebug($message, $context);
	}
	
	/**
	 * @param $message
	 * @param $context
	 */
	public function info($message, $context = '')
	{
		$context = $this->formatParamToArray($context);
		$this->addInfo($message, $context);
	}
	
	/**
	 * @param $message
	 * @param $context
	 */
	public function notice($message, $context = '')
	{
		$context = $this->formatParamToArray($context);
		$this->addNotice($message, $context);
	}
	
	/**
	 * @param $message
	 * @param $context
	 */
	public function warning($message, $context = '')
	{
		$context = $this->formatParamToArray($context);
		$this->addWarning($message, $context);
	}
	
	/**
	 * @param \Throwable $e
	 * @param            $context
	 */
	public function error($e, $context = '')
	{
		$context = $this->formatParamToArray($context);
		if ($this->isErrorObject($e) === false) {
			throw new \InvalidArgumentException(
				$this->generateInvalidArgumentMessage(__METHOD__)
			);
		}
		$this->addError(get_class($e), $this->formatPhpJsonLoggerErrorsContext($e, $context));
	}
	
	/**
	 * @param \Throwable $e
	 * @param            $context
	 */
	public function critical($e, $context = '')
	{
		$context = $this->formatParamToArray($context);
		if ($this->isErrorObject($e) === false) {
			throw new \InvalidArgumentException(
				$this->generateInvalidArgumentMessage(__METHOD__)
			);
		}
		$this->addCritical(get_class($e), $this->formatPhpJsonLoggerErrorsContext($e, $context));
	}
	
	/**
	 * @param \Throwable $e
	 * @param            $context
	 */
	public function alert($e, $context = '')
	{
		$context = $this->formatParamToArray($context);
		if ($this->isErrorObject($e) === false) {
			throw new \InvalidArgumentException(
				$this->generateInvalidArgumentMessage(__METHOD__)
			);
		}
		$this->addAlert(get_class($e), $this->formatPhpJsonLoggerErrorsContext($e, $context));
	}
	
	/**
	 * @param \Throwable $e
	 * @param            $context
	 */
	public function emergency($e, $context = '')
	{
		$context = $this->formatParamToArray($context);
		if ($this->isErrorObject($e) === false) {
			throw new \InvalidArgumentException(
				$this->generateInvalidArgumentMessage(__METHOD__)
			);
		}
		$this->addEmergency(get_class($e), $this->formatPhpJsonLoggerErrorsContext($e, $context));
	}
	
	/**
	 * @param $value
	 * @return bool
	 */
	private function isErrorObject($value): bool
	{
		if ($value instanceof \Exception || $value instanceof \Error) {
			return true;
		}
		return false;
	}
	
	/**
	 * @param $value
	 * @return array
	 */
	private function formatParamToArray($value): array
	{
		if (is_array($value)) {
			return $value;
		} else {
			return (empty($value)) ? [] : ['context' => $value];
		}
	}
	
	/**
	 * @param string $method
	 * @return string
	 */
	private function generateInvalidArgumentMessage(string $method): string
	{
		return 'Please give the exception class to the ' . $method;
	}
}