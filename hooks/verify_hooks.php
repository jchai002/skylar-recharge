<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once(__DIR__.'/../includes/config.php');

$webhooks_required = [
	// Sync order
	[
		'type'=>'orders/create',
		'address' => 'https://ec2production.skylar.com/hooks/order_created.php',
	],
	// Sync order
	[
		'type'=>'orders/updated',
		'address' => 'https://ec2production.skylar.com/hooks/order_updated.php',
	],
	// Sync product
	[
		'type'=>'products/create',
		'address' => 'https://ec2production.skylar.com/hooks/product_updated.php',
	],
	// Sync product
	[
		'type'=>'products/update',
		'address' => 'https://ec2production.skylar.com/hooks/product_updated.php',
	],
	// Gift notification
	[
		'type'=>'fulfillments/update',
		'address' => 'https://ec2production.skylar.com/hooks/order_delivered.php',
	],
];

$sc = new ShopifyClient();

print_r($sc->call("GET", "/admin/oauth/access_scopes.json"));


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