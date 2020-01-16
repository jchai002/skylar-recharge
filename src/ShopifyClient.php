<?php

use GuzzleHttp\Client;

// Should either extend a guzzle client or wrap one
// List desired functionality

class ShopifyClient {
	/**
	 * @var Client $client
	 */
	private $client;
	private $api_version_string = 'admin/api/2019-04/';
	private $shop_domain = 'maven-and-muse.myshopify.com';
	private $token;

	function __construct(){
		$this->client = new GuzzleHttp\Client();
	}

	private function getBaseURL(){
		return "https://{$this->shop_domain}/{$this->api_version_string}";
	}

	private function getAuthHeader(){
		return 'X-Shopify-Access-Token: ' . $this->token;
	}

	// Replicate existing API
	public function call($method, $path, $params, $options){

	}
	public function get($path, $params = [], $options = []){}
	public function post($path, $params = [], $options = []){}
	public function put($path, $params = [], $options = []){}
	public function delete($path, $params = [], $options = []){}

	// Add ability to change api version at client level and on the fly
	// Add async methods
	// Add ability to rotate API keys
	// Add ability to make requests as private or public app on the fly
}