<?php

use GuzzleHttp\Client;
use GuzzleHttp\Middleware;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
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

	private $credentials = [];
	private $api_version_string = '/admin/api/2020-01';
	private $where_params_go = [
		'GET' => 'query',
		'DELETE' => 'query',
		'POST' => 'json',
		'PUT' => 'json',
	];

	function __construct($config = []){
		$this->addToken($_ENV['SHOPIFY_APP_TOKEN']);
		parent::__construct(array_merge([
			'base_uri' => "https://{$this->shop_domain}/{$this->api_version_string}/",
		], $config));
		$stack = $this->getConfig('handler');
		// Middleware for managing passing back tokens and setting calls remaining
		$stack->push(function(callable $handler){
			return function(RequestInterface $request, $options) use($handler) {
				/** @var Promise $promise */
				$promise = $handler($request, $options);
				return $promise->then(function(ResponseInterface $response) use($options) {
					if(!empty($options['token_key'])){
						$this->credentials[$options['token_key']]['calls_remaining'] = $this->getRateInfo($response)['left'];
					}
					return $response->withHeader('X-Token-Key', $options['token_key']);
				});
			};
		}, 'rate_limit_updater');
		// Middleware for parsing Json
		$stack->push(Middleware::mapResponse(function(ResponseInterface $response) {
			return new ShopifyResponse(
				$response->getStatusCode(),
				$response->getHeaders(),
				$response->getBody(),
				$response->getProtocolVersion(),
				$response->getReasonPhrase()
			);
		}), 'shopify_json_middleware');
	}

	// Theoretically Credential should be a class/interface
	private function getCredential($type = 'any'){
		foreach($this->credentials as $key=>$cred){
			if($type != 'any' && $cred['type'] != $type){
				continue;
			}
			if($cred['calls_remaining'] > 10){
				return $cred;
			}
		}
		foreach($this->credentials as $key=>$cred){
			if($type != 'any' && $cred['type'] != $type){
				continue;
			}
		}
		foreach($this->credentials as $key=>$cred){
			if($cred['calls_remaining'] > 10){
				return $cred;
			}
		}
		$key = array_keys($this->credentials)[0];
		return $this->credentials[$key];
	}

	public function addToken($token){
		$this->credentials[$token] = [
			'type' => 'public',
			'key' => $token,
			'options' => ['headers' => ['X-Shopify-Access-Token'=>$token], 'token_key' => $token],
			'calls_remaining' => 30,
		];
	}

	public function addSecret($key, $secret){
		$this->credentials[$key] = [
			'type' => 'private',
			'key' => $key,
			'options' => ['auth' => ['username' => $key, 'password' => $secret], 'token_key' => $key],
			'calls_remaining' => 20,
		];
	}

	// Add credentials to all requests
	public function requestAsync($method, $uri = '', array $options = []){
		$cred = $this->getCredential($options['credential_type'] ?? 'any');
		$options = array_merge_recursive($options, $cred['options']);
		$this->credentials[$cred['key']]['calls_remaining']--;
		return parent::requestAsync($method, $uri, $options);
	}

	// Replicate existing API
	public function call($method, $path, $params = [], $options = []){
		if(!empty($params)){
			$options[$this->where_params_go[$method]] = $params;
		}
		/* @var $response ShopifyResponse */
		$this->last_response = $response = $this->request($method, $path, $options);
		$this->last_response_headers = $response->getHeaders();
		if($response->getStatusCode() != 200){
			$this->last_error = $response->getJson();
			return false;
		}
		return $response->getJson();
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

	private function getRateInfo(ResponseInterface $response){
		$rate_info = explode('/', $response->getHeader('X-Shopify-Shop-Api-Call-Limit')[0]);
		return [
			'made' => $rate_info[0],
			'limit' => $rate_info[1],
			'left' => $rate_info[1]-$rate_info[0],
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

	public function totalCallsLeft(){
		return array_sum(array_column($this->credentials, 'calls_remaining'));
	}

}