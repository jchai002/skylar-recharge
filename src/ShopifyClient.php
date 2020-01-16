<?php

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;

// Should either extend a guzzle client or wrap one
// List desired functionality

class ShopifyClient {
	/**
	 * @var Client $client
	 */
	public $client;
	/**
	 * @var Response $last_response
	 */
	public $last_response;
	public $last_response_headers;
	public $last_error;
	public $shop_domain = 'maven-and-muse.myshopify.com';

	private $api_version_string = '/admin/api/2020-01';
	private $where_params_go = [
		'GET' => 'query',
		'DELETE' => 'query',
		'POST' => 'json',
		'PUT' => 'json',
	];

	function __construct(){
		$this->client = new GuzzleHttp\Client([
			'base_uri' => "https://{$this->shop_domain}/{$this->api_version_string}/",
			'headers' => [
				'X-Shopify-Access-Token' => $_ENV['SHOPIFY_APP_TOKEN'],
			],
		]);
	}

	// Replicate existing API
	public function call($method, $path, $params = [], $options = []){
		if(!empty($params)){
			$options[$this->where_params_go[$method]] = $params;
		}
		$this->last_response = $response = $this->client->request($method, $path, $options);
		$this->last_response_headers = $response->getHeaders();
		$res_data = json_decode((string) $response->getBody(), true);
		if($response->getStatusCode() != 200){
			$this->last_error = json_decode($res_data);
			return false;
		}
		return (is_array($res_data) and (count($res_data) > 0)) ? array_shift($res_data) : $res_data;
	}

	public function get($path, $params = [], $options = []){
		return $this->call("GET", $path, $params, $options);
	}

	public function post($path, $params = [], $options = []){
		return $this->call("POST", $path, $params, $options);
	}

	public function put($path, $params = [], $options = []){
		return $this->call("PUT", $path, $params, $options);
	}

	public function delete($path, $params = [], $options = []){
		return $this->call("DELETE", $path, $params, $options);
	}

	private function getRateInfo(Response $response){
		$rate_info = explode('/', $response->getHeader('X-Shopify-Shop-Api-Call-Limit')[0]);
		return [
			'made' => $rate_info[0],
			'limit' => $rate_info[1],
			'left' => $rate_info[1]-[$rate_info[0]],
		];
	}

	public function callsLeft(){
		return $this->getRateInfo($this->last_response)['left'];
	}

	public function callLimit(){
		return $this->getRateInfo($this->last_response)['limit'];
	}

	public function callsMade(){
		return $this->getRateInfo($this->last_response)['made'];
	}

	// Add ability to change api version at client level and on the fly
	// Add async methods
	// Add ability to rotate API keys
	// Add ability to make requests as private or public app on the fly
}