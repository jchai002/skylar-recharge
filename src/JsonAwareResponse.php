<?php

use GuzzleHttp\Psr7\Response;

class JsonAwareResponse extends Response
{
	/**
	 * Cache for performance
	 * @var array
	 */
	private $json;

	public function getJson(){
		if ($this->json) {
			return $this->json;
		}
		if (false === strpos($this->getHeaderLine('Content-Type'), 'application/json')) {
			return [];
		}
		// get parent Body stream
		$this->json = json_decode((string) parent::getBody(), true);
		return $this->json;
	}
}