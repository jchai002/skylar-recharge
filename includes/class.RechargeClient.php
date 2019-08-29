<?php
class RechargeClient {
    private $authTokens;
    private $tokenIndex = 0;

    public function __construct(){
		$this->authTokens = [$_ENV['RECHARGE_API_TOKEN']];
		if(!empty($_ENV['RECHARGE_API_TOKENS'])){
			$this->authTokens = explode(',',$_ENV['RECHARGE_API_TOKENS']);
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
        $response['path'] = $url;
        $response['headers'] = [];
        $response['data'] = $data;
        foreach(explode(PHP_EOL,trim($headers)) as $header){
        	$header_split = explode(':', trim($header));
			$response['headers'][array_shift($header_split)] = trim(implode(':', $header_split));
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

    function getAuthToken(){
    	$this->tokenIndex++;
    	$this->tokenIndex = $this->tokenIndex % count($this->authTokens);
    	return $this->authTokens[$this->tokenIndex];
	}

}