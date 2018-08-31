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
	log_event($db, 'log', $data);
	$order = json_decode($data, true);
}
if(empty($order)){
	die('no data');
}
$rc = new RechargeClient();

// Get recharge version of order
$rc_order = $rc->get('/orders',['shopify_order_id'=>$order['id']])['orders'][0];
var_dump($rc_order);
if(empty($rc_order)){
	die('no rc order');
}

$has_subscription_line_item = false;
$subs_to_create = [];
$sample_credit = 0;
foreach($order['line_items'] as $line_item){
	// TEMP: Skip for old sub type
	if(in_array($line_item['variant_id'], [738567520343,738394865751,738567323735])){
		die('variant '.$line_item['variant_id']);
	}
	if(in_array($line_item['variant_id'], $sample_credit_variant_ids)){
		$sample_credit = $line_item['price'];
	}
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
if(!empty($sample_credit)){
	$rc->put('/addresses/'.$rc_order['address_id'], [
		'cart_attributes' => ['_sample_credit' => $sample_credit],
	]);
}
if(empty($subs_to_create)){
	exit;
}

$delay_days = 17; // TODO: more if international
$order_created_time = strtotime($order['created_at']);
$offset = 0;
do {
	$next_charge_time = $order_created_time + (($delay_days+$offset) * 24*60*60);
	$offset++;
} while(!in_array(date('N', $next_charge_time), [6,7]));

$product_cache = [];

foreach($subs_to_create as $sub_data){
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
			'product_title' => $product['title'],
			'variant_title' => $variant['title'],
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
			'product_title' => $product['title'],
			'variant_title' => $variant['title'],
		]);
	}
	$subscription_id = $response['subscription']['id'];
	var_dump($response);
}