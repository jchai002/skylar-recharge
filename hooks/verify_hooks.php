<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once('../includes/config.php');

$webhooks_required = [
	// Sync order
	[
		'type'=>'orders/created',
		'address' => 'https://ec2production.skylar.com/hooks_rc/charge_created.php',
	],
];

require_once('../includes/class.ShopifyClient.php');
$sc = new ShopifyPrivateClient();

$webhooks = $sc->call("GET", "/admin/webhooks.json");

print_r($webhooks);

foreach($webhooks_required as $req_hook){
	$hook_exists = false;
	foreach($webhooks as $hook){
		if($hook['address'] == $req_hook['address'] && $hook['topic'] == $req_hook['type']){
			$hook_exists = true;
			break;
		}
	}
	if(!$hook_exists){
		echo "Creating webhook ".$req_hook['type'];
		$response = $sc->call("POST", "/admin/webhooks.json", ["webhook" => ["topic"=>$req_hook['type'], "address"=>$req_hook['address'], "format"=>"json"]]);
		print_r($response);
	}
}
