<?php

require_once('../includes/config.php');
require_once('../includes/class.ShopifyClient.php');
require_once('../includes/class.RechargeClient.php');

$headers = getallheaders();
$shop_url = null;
if(!empty($headers['X-Shopify-Shop-Domain'])){
	$shop_url = $headers['X-Shopify-Shop-Domain'];
}
if(empty($shop_url)){
	$shop_url = 'maven-and-muse.myshopify.com';
}

$sc = new ShopifyPrivateClient($shop_url);

if(!empty($_REQUEST['id'])){
	$order = $sc->call('GET', '/admin/orders/'.intval($_REQUEST['id']).'.json');
} else {
	$data = file_get_contents('php://input');
	$order = json_decode($data);
}
if(empty($order)){
	die('no data');
}

// Variants that are allowed to create subscriptions, eventually we won't use this but it's a good safeguard for now
$subscription_variant_ids = ['5672401895455'];
$ids_by_scent = [
	'arrow'  => ['variant' => 31022048003,   'product' => 8985085187],
	'capri'  => ['variant' => 5541512970271, 'product' => 443364081695],
	'coral'  => ['variant' => 26812012355,   'product' => 8215300931],
	'isle'   => ['variant' => 31022109635,   'product' => 8985117187],
	'meadow' => ['variant' => 26812085955,   'product' => 8215317379],
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
		if($property['name'] == '_sub_scent' && !empty($ids_by_scent[$property['value']])){
			$sub_scent = $property['value'];
		}
	}
	if(empty($sub_scent) || empty($sub_frequency)){
		$subs_to_create[] = [
			'ids' => $ids_by_scent[$sub_scent],
			'frequency' => $sub_frequency,
		];
	}
}
if(empty($subs_to_create)){
	exit;
}

// Get recharge version of order
$rc = new RechargeClient();
$rc_order = $rc->get('/orders',['shopify_order_id'=>$order['id']])['orders'][0];
if(empty($rc_order)){
	exit;
}
//var_dump($rc_order);

$delay_days = 17; // TODO: more if international
$order_created_time = strtotime($order['created_at']);
$next_charge_time = $order_created_time + ($delay_days * 24*60*60);
$product_cache = [];

foreach($subs_to_create as $sub_data){
	// TODO: We may need to check for existing subscriptions here? Need business logic
	if(empty($product_cache[$sub_data['ids']['product']])){
		$product = $sc->call('GET', '/admin/products/'.$sub_data['ids']['product'].'.json');
		$product_cache[$product['id']] = $product;
	} else {
		$product = $product_cache[$product['id']];
	}
	foreach($product['variants'] as $variant){
		if($variant['id'] == $sub_data['ids']['variant']){
			break;
		}
	}
	if($variant['id'] != $sub_data['ids']['variant']){
		continue;
	}

	if($sub_data['frequency'] == 'onetime'){
		$response = $rc->post('/onetimes/address/'.$rc_order['address_id'], [
			'address_id' => $rc_order['address_id'],
			'next_charge_scheduled_at' => date('Y-m-d', $next_charge_time),
			'shopify_variant_id' => $sub_data['ids']['variant'],
			'quantity' => 1,
			'price' => $variant['price'],
			'product_title' => $product['title']." ".$variant['title'],
		]);
	} else {
		$response = $rc->post('/subscriptions', [
			'address_id' => $rc_order['address_id'],
			'next_charge_scheduled_at' => date('Y-m-d', $next_charge_time),
			'shopify_variant_id' => $sub_data['ids']['variant'],
			'quantity' => 1,
			'order_interval_unit' => 'month',
			'order_interval_frequency' => $sub_data['frequency'],
			'charge_interval_frequency' => $sub_data['frequency'],
			'order_day_of_month' => date('d', $next_charge_time),
			'product_title' => $product['title']." ".$variant['title'],
		]);
	}
	var_dump($response);
}