<?php

use GuzzleHttp\Client;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

// Should either extend a guzzle client or wrap one
// List desired functionality

class ShopifyClient extends Client {
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
		parent::__construct([
			'base_uri' => "https://{$this->shop_domain}/{$this->api_version_string}/",
			'headers' => [
				'X-Shopify-Access-Token' => $_ENV['SHOPIFY_APP_TOKEN'],
			],
		]);
		$handler = $this->getConfig('handler');
		$handler->push(Middleware::mapResponse(function (ResponseInterface $response) {
			return new ShopifyResponse(
				$response->getStatusCode(),
				$response->getHeaders(),
				$response->getBody(),
				$response->getProtocolVersion(),
				$response->getReasonPhrase()
			);
		}), 'shopify_json_middleware');
	}

	// Replicate existing API
	public function call($method, $path, $params = [], $options = []){
		if(!empty($params)){
			$options[$this->where_params_go[$method]] = $params;
		}
		$this->last_response = $response = $this->request($method, $path, $options);
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

	// Add ability to change api version at client level and on the fly - Use URLs for now
	// Add async methods - Should these return json
	// Add ability to make requests as private or public app on the fly
	// Add ability to rotate API keys
}