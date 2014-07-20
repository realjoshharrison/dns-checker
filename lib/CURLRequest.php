<?php

class CURLRequest {

	protected $CURLHandle;

	public function __construct($url, $timeout = 3)
	{
		$this->initCURL();
		$this->setURL($url);
		$this->setTimeout($timeout);
	}

	protected function initCURL()
	{
		if( ! function_exists('curl_version')) {
			throw new CURLRequestException('cURL is not available.', 501);
		}
		$this->CURLHandle = curl_init();
		curl_setopt($this->CURLHandle, CURLOPT_RETURNTRANSFER, 1);
	}

	protected function setURL($url)
	{
		if(empty($url)) {
			throw new CURLRequestException('URL is required.', 400);
		}
		curl_setopt($this->CURLHandle, CURLOPT_URL, $url);
	}

	protected function setTimeout($timeout)
	{
		if( ! is_int($timeout)) {
			throw new CURLRequestException('Timeout must be an integer.', 400);
		}
		curl_setopt($this->CURLHandle, CURLOPT_CONNECTTIMEOUT, $timeout);
	}

	public function get()
	{
		if($data = curl_exec($this->CURLHandle)) {
			curl_close($this->CURLHandle);
			return $data;
		} else {
			throw new CURLRequestException(curl_error($this->CURLHandle), curl_errno($this->CURLHandle));
		}
	}

}

class CURLRequestException extends Exception {

	public function __toString()
	{
		return __CLASS__ . ": {$this->message}\n";
	}

}

// EOF