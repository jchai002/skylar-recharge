<?php

require_once('includes/config.php');
require_once('includes/class.RechargeClient.php');

$rc = new RechargeClient();

$headers = getallheaders();
$shop_url = null;
if(!empty($headers['X-Shopify-Shop-Domain'])){
	$shop_url = $headers['X-Shopify-Shop-Domain'];
}
if(empty($shop_url)){
	$shop_url = 'maven-and-muse.myshopify.com';
}

$data = file_get_contents('php://input');

// TODO: don't use cache live
$cache_file = 'last_order_created.txt';
if(empty($data)){
	if(file_exists($cache_file)){
		$data = file_get_contents($cache_file);
	}
} else {
	die('temp');
	file_put_contents($cache_file, $data);
}
if(empty($data)){
	die("No cache file");
}
$order = json_decode($data, true);

var_dump($order);

// Variants that are allowed to create subscriptions, eventually we won't use this but it's a good safeguard for now
$subscription_variant_ids = ['5672401895455'];
$variant_ids_by_scent = [
	'arrow' => 31022048003,
	'capri' => 5541512970271,
	'coral' => 26812012355,
	'isle' => 31022109635,
	'meadow' => 26812085955,
];

$has_subscription_line_item = false;
$subs_to_create = [];
foreach($order['line_items'] as $line_item){
	if(!in_array($line_item['variant_id'], $subscription_variant_ids)){
		continue;
	}
	if(empty($line_item['properties'])){
		continue;
	}
	$sub_scent = $sub_frequency = null;
	foreach($line_item['properties'] as $property){
		if($property['name'] == '_sub_frequency'){
			$sub_frequency = intval($property['value']);
		}
		if($property['name'] == '_sub_scent' && !empty($variant_ids_by_scent[$property['value']])){
			$sub_scent = intval($property['value']);
		}
	}
	if(empty($sub_scent) || empty($sub_frequency)){
		$subs_to_create[] = [
			'variant_id' => $variant_ids_by_scent[$sub_scent],
			'frequency' => $sub_frequency
		];
	}
}
if(empty($subs_to_create)){
	// exit;
}

// Get recharge version of order
$rc_order = $rc->get('/orders',['shopify_order_id'=>$order['id']]);
var_dump($rc_order);


foreach($subs_to_create as $sub_data){
	// TODO: We may need to check for existing subscriptions here? Need business logic
	// Need to consider onetime case, maybe use one-time api
	// $rc->put()
}