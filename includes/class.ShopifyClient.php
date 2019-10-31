<?php
if(!empty($_ENV['SHOPIFY_APP_TOKEN'])){
	define('SHOPIFY_PRIVATE_APP_KEY',$_ENV['SHOPIFY_PRIVATE_APP_KEY']);
	define('SHOPIFY_PRIVATE_APP_SECRET',$_ENV['SHOPIFY_PRIVATE_APP_SECRET']);
	define('SHOPIFY_APP_KEY', $_ENV['SHOPIFY_APP_KEY']);
	define('SHOPIFY_APP_SECRET',$_ENV['SHOPIFY_APP_SECRET']);
	define('SHOPIFY_APP_TOKEN',$_ENV['SHOPIFY_APP_TOKEN']);
} else {
	define('SHOPIFY_PRIVATE_APP_KEY',$_ENV['SHOPIFY_PRIVATE_APP_KEY']);
	define('SHOPIFY_PRIVATE_APP_SECRET',$_ENV['SHOPIFY_PRIVATE_APP_SECRET']);
	define('SHOPIFY_APP_KEY', $_ENV['SHOPIFY_APP_KEY']);
	define('SHOPIFY_APP_SECRET',$_ENV['SHOPIFY_APP_SECRET']);
}
define('SHOPIFY_SCOPE',implode(',',[
	'read_content',
	'write_content',
	'read_themes',
	'write_themes',
	'read_products',
	'write_products',
	'read_customers',
	'write_customers',
	'read_orders',
	'write_orders',
	'read_all_orders',
	'read_draft_orders',
	'write_draft_orders',
	'read_inventory',
	'write_inventory',
	'read_locations',
	'read_script_tags',
	'write_script_tags',
	'read_fulfillments',
	'write_fulfillments',
	'read_shipping',
	'write_shipping',
	'read_analytics',
//	'read_users',
//	'write_users',
	'read_checkouts',
	'write_checkouts',
	'read_reports',
	'write_reports',
	'read_price_rules',
	'write_price_rules',
	'read_discounts',
	'write_discounts',
	'read_marketing_events',
	'write_marketing_events',
]));
class ShopifyClient {
	public $shop_domain;
	public $token;
	public $api_key;
	public $secret;
	public $last_response_headers = null;
	public $last_error;
	public static $token_lookup = [
        'maven-and-muse.myshopify.com' => '',
    ];

	public function __construct($shop_domain = '', $token = '', $api_key = SHOPIFY_APP_KEY, $secret = SHOPIFY_APP_SECRET){
		$this->name = "ShopifyClient";
		$this->shop_domain = $shop_domain;
		if(empty($this->shop_domain)){
			if(!empty(ShopifyClient::$token_lookup)){
				$this->shop_domain = array_keys(ShopifyClient::$token_lookup)[0];
			}
		}
		$this->token = $token;
		if(empty($this->token) && defined('SHOPIFY_APP_TOKEN')){
			$this->token = SHOPIFY_APP_TOKEN;
		}
		if(empty($this->token)){
			if(array_key_exists($this->shop_domain, ShopifyClient::$token_lookup)){
				$this->token = ShopifyClient::$token_lookup[$this->shop_domain];
			}
		}
		$this->api_key = $api_key;
		$this->secret = $secret;
	}

	// Get the URL required to request authorization
	public function getAuthorizeUrl($scope, $redirect_url='') {
		$url = "https://{$this->shop_domain}/admin/oauth/authorize?client_id={$this->api_key}&scope=" . urlencode($scope);
		if ($redirect_url != '')
		{
			$url .= "&redirect_uri=" . urlencode($redirect_url);
		}
		return $url;
	}

	// Once the User has authorized the app, call this with the code to get the access token
	public function getAccessToken($code) {
		// POST to  POST https://SHOP_NAME.myshopify.com/admin/oauth/access_token
		$url = "https://{$this->shop_domain}/admin/oauth/access_token";
		$payload = "client_id={$this->api_key}&client_secret={$this->secret}&code=$code";
		$response = $this->curlHttpApiRequest('POST', $url, '', $payload, array());
		$response = json_decode($response, true);
		if (isset($response['access_token']))
			return $response['access_token'];
		return '';
	}

	public function callsMade()
	{
		return $this->shopApiCallLimitParam(0);
	}

	public function callLimit()
	{
		return $this->shopApiCallLimitParam(1);
	}

	public function callsLeft()
	{
		return $this->callLimit() - $this->callsMade();
	}

	public function getBaseURL(){
		return "https://{$this->shop_domain}/";
	}

	public function getAuthHeader(){
		return 'X-Shopify-Access-Token: ' . $this->token;
	}

	public function call($method, $path, $params=array())
	{
		$baseurl = $this->getBaseURL();

		$url = $baseurl.ltrim($path, '/');
		$query = in_array($method, array('GET','DELETE')) ? $params : array();
		$payload = in_array($method, array('POST','PUT')) ? json_encode($params) : array();
		$request_headers = in_array($method, array('POST','PUT')) ? array("Content-Type: application/json; charset=utf-8", 'Expect:') : array();

		// add auth headers
		$auth_header = $this->getAuthHeader();
		if(!empty($auth_header)){
			$request_headers[] = $auth_header;
		}

		$response = $this->curlHttpApiRequest($method, $url, $query, $payload, $request_headers);
		$response = json_decode($response, true);

		if (isset($response['errors']) or ($this->last_response_headers['http_status_code'] >= 400)){
			$this->last_error = $response;
			return false;
			throw new ShopifyApiException($method, $path, $params, $this->last_response_headers, $response);
		}

		return (is_array($response) and (count($response) > 0)) ? array_shift($response) : $response;
	}

	public function get($path, $params = []){
		return $this->call("GET", $path, $params);
	}

	public function post($path, $params = []){
		return $this->call("POST", $path, $params);
	}

	public function put($path, $params = []){
		return $this->call("PUT", $path, $params);
	}

	public function delete($path, $params = []){
		return $this->call("DELETE", $path, $params);
	}

	public function validateSignature($query)
	{
		if(!is_array($query) || empty($query['hmac']) || !is_string($query['hmac']))
			return false;

		$dataString = array();
		foreach ($query as $key => $value) {
			if(!in_array($key, array('shop', 'timestamp', 'code'))) continue;

			$key = str_replace('=', '%3D', $key);
			$key = str_replace('&', '%26', $key);
			$key = str_replace('%', '%25', $key);

			$value = str_replace('&', '%26', $value);
			$value = str_replace('%', '%25', $value);

			$dataString[] = $key . '=' . $value;
		}
		sort($dataString);

		$string = implode("&", $dataString);

		$signatureBin = mhash(MHASH_SHA256, $string, $this->secret);
		$signature = bin2hex($signatureBin);

		return $query['hmac'] == $signature;
	}

	private function curlHttpApiRequest($method, $url, $query='', $payload='', $request_headers=array())
	{
		$url = $this->curlAppendQuery($url, $query);
		$ch = curl_init($url);
		$this->curlSetopts($ch, $method, $payload, $request_headers);
		$response = curl_exec($ch);
		$errno = curl_errno($ch);
		$error = curl_error($ch);
		curl_close($ch);

//		if ($errno) throw new ShopifyCurlException($error, $errno);
		if ($errno) return false;
		list($message_headers, $message_body) = preg_split("/\r\n\r\n|\n\n|\r\r/", $response, 2);
		$this->last_response_headers = $this->curlParseHeaders($message_headers);

		return $message_body;
	}

	private function curlAppendQuery($url, $query)
	{
		if (empty($query)) return $url;
		if (is_array($query)) return "$url?".http_build_query($query);
		else return "$url?$query";
	}

	private function curlSetopts($ch, $method, $payload, $request_headers)
	{
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($ch, CURLOPT_USERAGENT, 'ohShopify-php-api-client');
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);

		curl_setopt ($ch, CURLOPT_CUSTOMREQUEST, $method);
		if (!empty($request_headers)) curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);

		if ($method != 'GET' && !empty($payload))
		{
			if (is_array($payload)) $payload = http_build_query($payload);
			curl_setopt ($ch, CURLOPT_POSTFIELDS, $payload);
		}
	}

	private function curlParseHeaders($message_headers)
	{
		$header_lines = preg_split("/\r\n|\n|\r/", $message_headers);
		$headers = array();
		list(, $headers['http_status_code'], $headers['http_status_message']) = explode(' ', trim(array_shift($header_lines)), 3);
		foreach ($header_lines as $header_line)
		{
			list($name, $value) = explode(':', $header_line, 2);
			$name = strtolower($name);
			$headers[$name] = trim($value);
		}

		return $headers;
	}

	private function shopApiCallLimitParam($index)
	{
		if ($this->last_response_headers == null)
		{
			return false;
			throw new Exception('Cannot be called before an API call.');
		}
		$params = explode('/', $this->last_response_headers['http_x_shopify_shop_api_call_limit']);
		return (int) $params[$index];
	}
}
class ShopifyPrivateClient extends ShopifyClient {

	public function __construct($shop_domain = 'maven-and-muse.myshopify.com', $token = '', $api_key = SHOPIFY_PRIVATE_APP_KEY, $secret = SHOPIFY_PRIVATE_APP_SECRET){
		parent::__construct($shop_domain, $token, $api_key, $secret);
		$this->name = "ShopifyPrivateClient";
	}

	public function getBaseURL(){
		return "https://{$this->api_key}:{$this->secret}@{$this->shop_domain}/";
	}


	public function getAuthHeader(){
		return '';
	}

}
class ShopifyCurlException extends Exception { }
class ShopifyApiException extends Exception
{
	protected $method;
	protected $path;
	protected $params;
	protected $response_headers;
	protected $response;

	function __construct($method, $path, $params, $response_headers, $response)
	{
		$this->method = $method;
		$this->path = $path;
		$this->params = $params;
		$this->response_headers = $response_headers;
		$this->response = $response;

		parent::__construct($response_headers['http_status_message'], $response_headers['http_status_code']);
	}

	function getMethod() { return $this->method; }
	function getPath() { return $this->path; }
	function getParams() { return $this->params; }
	function getResponseHeaders() { return $this->response_headers; }
	function getResponse() { return $this->response; }
}

function verify_webhook($data, $hmac_header)
{
	$calculated_hmac = base64_encode(hash_hmac('sha256', $data, SHOPIFY_APP_SECRET, true));
	return ($hmac_header == $calculated_hmac);
}