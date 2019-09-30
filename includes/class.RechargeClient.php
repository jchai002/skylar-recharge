<?php
class RechargeClient {
    private $authTokens = [];
    private $tokenIndex = 0;
    public $debug = false;

    public function __construct(){
		if(!empty($_ENV['RECHARGE_API_TOKENS'])){
			foreach(explode(',',$_ENV['RECHARGE_API_TOKENS']) as $token){
				$this->authTokens[] = [
					'token' => $token,
					'calls_remaining' => 40,
					'bucket_size' => 40,
				];
			}
		} elseif(!empty($_ENV['RECHARGE_API_TOKEN'])){
			$this->authTokens = [
				'token' => $_ENV['RECHARGE_API_TOKEN'],
			];
		}
	}

	function call($url, $data = [], $method='GET'){
    	if(strpos($url, 'webhook') !== false){
    		$this->tokenIndex = count($this->authTokens)-1;
		}
        $method = strtoupper($method);
        $ch = curl_init();
        $original_url = $url;
        $url = 'https://api.rechargeapps.com/'.trim($url,'/');
        $data['retry'] = empty($data['retry']) ? 0 : $data['retry'];
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => TRUE,
			CURLOPT_HEADER => TRUE,
            CURLOPT_HTTPHEADER => [
                'x-recharge-access-token: ' . $this->getAuthToken(),
                'Content-Type: application/json',
            ],
        ]);
        if($method == 'GET'){
            curl_setopt_array($ch, [
                CURLOPT_POST => false,
            ]);
            if(!empty($data)){
                $url .= strpos($url, '?') === false ? '?' : '&';
                $url .= http_build_query($data);
            }
        } else if($method == 'POST'){
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($data),
            ]);
        } else {
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_CUSTOMREQUEST => $method,
            ]);
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        $response = curl_exec($ch);

		$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$headers = substr($response, 0, $header_size);
		$body = substr($response, $header_size);

        $response = json_decode($body, true);
		$response['path'] = $original_url;
		$response['method'] = $method;
        $response['headers'] = [];
        $response['data'] = $data;
        foreach(explode(PHP_EOL,trim($headers)) as $header){
        	$header_split = explode(':', trim($header));
			$response['headers'][array_shift($header_split)] = trim(implode(':', $header_split));
		}
        if(!empty($response['headers']['X-Recharge-Limit'])){
        	$rate_info = explode('/', $response['headers']['X-Recharge-Limit']);
			$this->authTokens[$this->tokenIndex]['bucket_size'] = $rate_info[1];
			$this->authTokens[$this->tokenIndex]['calls_remaining'] = $rate_info[1]-$rate_info[0];
		}

        if(
		(!empty($response['warning']) && $response['warning'] == 'too many requests' && $data['retry'] < 5)
		|| (!empty($response['error']) && $response['error'] == 'A call to this route is already in progress.')){
        	$data['retry']++;
        	sleep(5*$data['retry']);
        	return $this->call($original_url, $data, $method);
		}

        return $response;
    }

    function get($url, $data=[]){
        return $this->call($url, $data, 'GET');
    }

    function post($url, $data=[]){
        return $this->call($url, $data, 'POST');
    }

    function put($url, $data=[]){
        return $this->call($url, $data, 'PUT');
    }

    function delete($url, $data=[]){
        return $this->call($url, $data, 'DELETE');
    }

    function asyncCall($url, $data = [], $method='GET', $callback = null){

	}

    function getAuthToken(){
    	$this->tokenIndex++;
		$this->tokenIndex = $this->tokenIndex % count($this->authTokens);
		echo $this->debug ? "[DEBUG] Rotating Token to ".$this->tokenIndex.", ".$this->authTokens[$this->tokenIndex]['calls_remaining']." calls available".PHP_EOL : "";
    	if($this->authTokens[$this->tokenIndex]['calls_remaining'] < 3){
			echo $this->debug ? "[DEBUG] Not enough calls avail, checking other tokens".PHP_EOL : "";
    		foreach($this->authTokens as $index => $token_info){
    			if($token_info['calls_remaining'] > 10){
					$this->authTokens[$this->tokenIndex]['calls_remaining']++;
    				$this->tokenIndex = $index;
					echo $this->debug ? "[DEBUG] Jumping to token ".$this->tokenIndex.", ".$this->authTokens[$this->tokenIndex]['calls_remaining']." calls available".PHP_EOL : "";
    				break;
				}
			}
		}
    	return $this->authTokens[$this->tokenIndex]['token'];
	}

}