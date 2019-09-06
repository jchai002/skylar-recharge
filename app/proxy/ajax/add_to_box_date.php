<?php

global $db;
$rc = new RechargeClient();
$sc = new ShopifyClient();

if(!empty($_REQUEST['parent_id'])){
	$main_sub = get_rc_subscription($db, $_REQUEST['parent_id'], $rc, $sc);
	$address_id = $main_sub['recharge_address_id'];
} else {
	$main_sub = sc_get_main_subscription($db, $rc, [
		'status' => 'ACTIVE',
		'shopify_customer_id' => $_REQUEST['c'],
	]);
	$address_id = $main_sub['address_id'];
}

$variant = get_variant($db, $_REQUEST['variant_id']);
$product = get_product($db, $variant['shopify_product_id']);

$frequency = empty($_REQUEST['frequency']) ? 'onetime' : $_REQUEST['frequency'];

$price = get_subscription_price($product, $variant);
$res_id = false;
$properties = [];
if(!empty($_REQUEST['parent_id'])){
	$properties['_parent_id'] = $_REQUEST['parent_id'];
}
if(!is_numeric($frequency) || $frequency < 1 || $frequency > 12){
	$res = $rc->post('/addresses/'.$address_id.'/onetimes', [
		'address_id' => $address_id,
		'next_charge_scheduled_at' => date('Y-m-d', $_REQUEST['ship_time']),
		'product_title' => $product['title'],
		'price' => $price,
		'quantity' => 1,
		'shopify_variant_id' => $variant['shopify_id'],
		'properties' => $properties,
	]);
	$res_id = $res['onetime']['id'];
} else {
	$res = $rc->post('/addresses/'.$address_id.'/subscriptions', [
		'address_id' => $address_id,
		'next_charge_scheduled_at' => date('Y-m-d', $_REQUEST['ship_time']),
		'product_title' => $product['title'],
		'price' => $price,
		'quantity' => 1,
		'shopify_variant_id' => $variant['shopify_id'],
		'order_interval_unit' => 'month',
		'order_interval_frequency' => $frequency,
		'charge_interval_frequency' => $frequency,
		'order_day_of_month' => $main_sub['order_day_of_month'],
		'properties' => $properties,
	]);
	$res_id = $res['subscription']['id'];
}

if(!empty($res['error'])){
	echo json_encode([
		'success' => false,
		'error' => $res['error'],
		'res' => $res,
	]);
} else {
	echo json_encode([
		'success' => true,
		'id' => $res_id,
		'res' => $res,
		'product' => $product,
		'sc_month' => is_scent_club_month($product),
	]);
}