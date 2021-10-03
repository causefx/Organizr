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
			$path = (strpos($file, '/') !== false) ? explode('/', $file)[0] . '/' : '';
			$data = $this->userDefinedIdReplacementLink($data, ['plugins/images/cache/' => 'api/demo_data/' . $path . 'images/']);
			$data = json_decode($data, true);
			$this->setResponse(200, 'Demo data for file: ' . $file, $data['response']['data']);
			return $data['response']['data'];
		} else {
			$this->setResponse(404, 'Demo data was not found for file: ' . $file);
			return false;
		}
	}
}