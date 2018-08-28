<?php
require_once('../includes/config.php');
require_once('../includes/class.ShopifyClient.php');
require_once('../includes/class.RechargeClient.php');

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
if(empty($_REQUEST['customer_id'])){
	die(json_encode([
		'success' => false,
		'errors' => [
			['message' => 'No customer ID'],
		]
	]));
}
if(empty($_REQUEST['subscription_ids'])){
	die(json_encode([
		'success' => false,
		'errors' => [
			['message' => 'No subscription IDs'],
		]
	]));
}
if(empty($_REQUEST['add_product'])){
	die(json_encode([
		'success' => false,
		'errors' => [
			['message' => 'No product to add'],
		]
	]));
}

$sc = new ShopifyPrivateClient();
$product = $sc->call('GET', '/admin/products/'.intval($_REQUEST['add_product']['product_id']).'.json');

foreach($product['variants'] as $variant){
	if($variant['id'] == $_REQUEST['add_product']['variant_id']){
		break;
	}
}
if(empty($variant) || $variant['id'] != $_REQUEST['add_product']['variant_id']){
	die(json_encode([
		'success' => false,
		'errors' => [
			['message' => 'Invalid Product'],
		]
	]));
}


$subscription_ids = explode(',',$_REQUEST['subscription_ids']);

$rc = new RechargeClient();
$subscriptions = $rc->get('/subscriptions', [
	'shopify_customer_id' => $_REQUEST['customer_id'],
]);
if(empty($subscriptions['subscriptions'])){
	die(json_encode($subscriptions));
}
$subscriptions = $subscriptions['subscriptions'];
$customer_subscription_ids = array_column($subscriptions, 'id');

$address_id = 0;
foreach($subscriptions as $subscription){
	if(in_array($subscription['id'], $subscription_ids)){
		break;
	}
}
if(empty($subscription)){
	die(json_encode([
		'success' => false,
		'errors' => [
			['message' => 'Access denied'],
		]
	]));
}
$frequency = $subscription['status'] == 'ONETIME' ? 'onetime' : $subscription['order_interval_frequency'];

// TODO: Pricing rules
// TODO: Check discount

$new_subscription = add_subscription($rc, $product, $variant, $subscription['address_id'], strtotime($subscription['next_charge_scheduled_at']), 1, $frequency);
if(!empty($new_subscription)){
	$subscriptions[] = $new_subscription;
	$subscription_ids[] = $new_subscription['id'];
}

$addresses = [];
$addresses_res = $rc->get('/customers/'.$subscriptions[0]['customer_id'].'/addresses');
if(empty($addresses_res['addresses'])){
	die(json_encode($subscriptions));
}
foreach($addresses_res['addresses'] as $address_res){
	$addresses[$address_res['id']] = $address_res;
}

echo json_encode([
	'success' => !empty($new_subscription),
	'subscriptions' => group_subscriptions($subscriptions, $addresses),
	'subscriptions_raw' => $subscriptions,
	'show_ids' => implode(',',$subscription_ids),
]);