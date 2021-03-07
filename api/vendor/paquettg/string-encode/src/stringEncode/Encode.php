<?php
namespace stringEncode;

class Encode {

	/**
	 * The encoding that the string is currently in.
	 *
	 * @var string
	 */
	protected $from;
	
	/**
	 * The encoding that we would like the string to be in.
	 *
	 * @var string
	 */
	protected $to;

	/**
	 * Sets the default charsets for thie package.
	 */
	public function __construct()
	{
		// default from encoding
		$this->from = 'CP1252';

		// default to encoding
		$this->to = 'UTF-8';
	}

	/**
	 * Sets the charset that we will be converting to.
	 *
	 * @param string $charset
	 * @chainable
	 */
	public function to($charset)
	{
		$this->to = strtoupper($charset);
		return $this;
	}

	/**
	 * Sets the charset that we will be converting from.
	 *
	 * @param string $charset
	 * @chainable
	 */
	public function from($charset)
	{
		$this->from = strtoupper($charset);
	}

	/**
	 * Returns the to and from charset that we will be using.
	 *
	 * @return array
	 */
	public function charset()
	{
		return [
			'from' => $this->from,
			'to'   => $this->to,
		];
	}

	/**
	 * Attempts to detect the encoding of the given string from the encodingList.
	 *
	 * @param string $str
	 * @param array $encodingList
	 * @return bool
	 */
	public function detect($str, $encodingList = ['UTF-8', 'CP1252'])
	{
		$charset = mb_detect_encoding($str, $encodingList);
		if ($charset === false)
		{
			// could not detect charset
			return false;
		}

		$this->from = $charset;
		return true;
	}

	/**
	 * Attempts to convert the string to the proper charset.
	 *
	 * @return string
	 */
	public  function convert($str)
	{
		if ($this->from != $this->to)
		{
			$str = iconv($this->from, $this->to, $str);
		}

		if ($str === false)
		{
			// the convertion was a failure
			throw new Exception('The convertion from "'.$this->from.'" to "'.$this->to.'" was a failure.');
		}

		// deal with BOM issue for utf-8 text
		if ($this->to == 'UTF-8')
		{
			if (substr($str, 0, 3) == "\xef\xbb\xbf")
			{
				$str = substr($str, 3);
			}
			if (substr($str, -3, 3) == "\xef\xbb\xbf")
			{
				$str = substr($str, 0, -3);
			}
		}

		return $str;
	}
}
