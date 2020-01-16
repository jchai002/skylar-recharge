<?php

use GuzzleHttp\Client;

class ShopifyClient {
	/**
	 * @var Client $client
	 */
	private $client;

	function __construct(){
		$this->client = new GuzzleHttp\Client();
	}

	public function get($path, $params = [], $options = []){}
	public function post($path, $params = [], $options = []){}
	public function put($path, $params = [], $options = []){}
	public function delete($path, $params = [], $options = []){}

}