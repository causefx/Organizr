<?php
/** @noinspection PhpUndefinedFieldInspection */

trait DemoFunctions
{
	public function demoData($file = null)
	{
		if (!$file) {
			$this->setResponse(422, 'Demo file was not supplied');
			return false;
		}
		$path = dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . 'demo_data' . DIRECTORY_SEPARATOR . $file;
		if (file_exists($path)) {
			
			$data = file_get_contents($path);
			$data = json_decode($data, true);
			$this->setResponse(200, 'Demo data for file: ' . $file, $data['response']['data']);
			return $data;
		} else {
			$this->setResponse(404, 'Demo data was not found for file: ' . $file);
			return false;
		}
	}
}