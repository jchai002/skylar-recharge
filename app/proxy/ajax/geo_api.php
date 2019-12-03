<?php
require_once(__DIR__.'/../../../includes/config.php');

if(!empty($_SERVER['REMOTE_ADDR'])){
	$ip = $_SERVER['REMOTE_ADDR'];
}
if(function_exists('getallheaders')){
	$headers = getallheaders();
	if(!empty($headers['X-Forwarded-For'])){
		$ip = $headers['X-Forwarded-For'];
	}
	if(!empty($headers['Origin'])){
		$origin = parse_url($headers['Origin']);
		if(in_array($origin['host'], [
			'localhost',
			'maven-and-muse.myshopify.com',
			'skylar.com'
		]) || strpos($origin['host'], 'shopify_preview.com') !== false){
			header("Access-Control-Allow-Origin: ".$headers['Origin']);
		}
	}
}
if(!empty($_REQUEST['ip'])){
	$ip = $_REQUEST['ip'];
}
if(!empty($argv) && !empty($argv[1])){
	$ip = $argv[1];
}

header('Content-Type: application/json');
$ch = curl_init("http://api.ipstack.com/$ip?access_key=".$_ENV['IPSTACK_API_KEY']);
curl_exec($ch);