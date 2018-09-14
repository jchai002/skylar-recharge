<?php
class RechargeClient {
    private $authToken;

    public function __construct(){
		$this->authToken = $_ENV['RECHARGE_API_TOKEN'];
	}

	function call($url, $data = [], $method='GET'){
        $method = strtoupper($method);
        $ch = curl_init();
        $url = 'https://api.rechargeapps.com/'.trim($url,'/');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_HTTPHEADER => [
                'x-recharge-access-token: ' . $this->authToken,
                'Content-Type: application/json'
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
        $response = json_decode($response, true);

        if(!empty($response['warning']) && $response['warning'] == 'too many requests' && empty($data['retry'])){
        	$data['retry'] = 1;
        	sleep(5);
        	return $this->call($url, $data, $method);
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

}