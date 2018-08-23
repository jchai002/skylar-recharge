<?php
class RechargeClient {
    private $authToken = '069f9828d19cdfea4f5a73f43d28a891257dab5e5c03afc9f68a1e0e';

    function call($url, $data = [], $method='GET'){
        $method = strtoupper($method);
        $ch = curl_init();
        $url = 'https://api.rechargeapps.com/'.trim($url,'/');
        echo $url;
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
        return json_decode($response, true);
    }

    function get($url, $data){
        return $this->call($url, $data, 'GET');
    }

    function post($url, $data){
        return $this->call($url, $data, 'POST');
    }

    function put($url, $data){
        return $this->call($url, $data, 'PUT');
    }

    function delete($url, $data){
        return $this->call($url, $data, 'DELETE');
    }

}