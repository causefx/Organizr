<?php

class Ping
{

	private $urls;
	private $original_urls;
	private $valid_codes;
	private $ping_times = array();
	private $results = array();
	private $start_time;

	/**
	 * Called when the Ping object is created.
	 *
	 * @param array $urls
	 *   An array of URLs to be pinged
	 * @param array $original_urls
	 *   An array of URLs as shown in the UI before cleanup
	 * @param array $valid_codes
	 *   An array of valid HTTP codes
	 * @throws \Exception if any parameter is not set
	 */
	public function __construct($urls, $original_urls, $valid_codes)
	{
		if (!isset($urls)) {
			throw new \Exception("Error: urls not supplied.");
		}
		if (!isset($original_urls)) {
			throw new \Exception("Error: original_urls not supplied.");
		}
		if (!isset($valid_codes)) {
			throw new \Exception("Error: valid_codes not supplied.");
		}
		$this->urls = $urls;
		$this->valid_codes = $valid_codes;
		$this->original_urls = $original_urls;
	}

	public function callback($response, $i) {
		$url = $this->urls[$i]['url'];
		$code_str = str_replace(' ', '', $this->valid_codes[$i]);
		$codes = explode(',', $code_str);
		// If code is unset, assume 2xx,3xx
		if ($codes == '') {
			$codes = '2xx,3xx';
		}
		$this->results[$this->original_urls[$i]] = false;
		if (!$response instanceof Requests_Response) {
			return;
		}
		foreach ($codes as $code) {
			// Remove all "x" from the code, and then check if the response code starts with our valid code.
			$code = str_replace('x' , '', $code);
			if (substr($code, 0, strlen($response->status_code)) === $code) {
				$this->results[$this->original_urls[$i]] = round((microtime(true) - $this->start_time)*1000);
				return;
			}
		}
	}

	public function send_pings() {
		$hooks = new Requests_Hooks();
		$hooks->register('multiple.request.complete', array($this, 'callback'));

		// Fire all requests at the same time, asynchronusly
		$this->start_time = microtime(true);
		$responses = Requests::request_multiple($this->urls, array('hooks' => $hooks));
		return $this->results;
	}
}
