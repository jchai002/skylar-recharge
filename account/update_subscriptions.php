<?php
require_once('../includes/config.php');
require_once('../includes/class.ShopifyClient.php');
require_once('../includes/class.RechargeClient.php');

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Methods: POST, GET, DELETE, PUT, PATCH, OPTIONS');
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

$data = [];
if(!empty($_REQUEST['frequency'])){
	$data['order_interval_frequency'] = $data['charge_interval_frequency'] = intval($_REQUEST['frequency']);
	$data['interval_unit_type'] = 'month';
}
if(!empty($_REQUEST['quantity'])){
	$data['quantity'] = intval($_REQUEST['quantity']);
	// TODO: Pricing rules
}
// TODO: Check discount
foreach($subscription_ids as $subscription_id){
	$updated_subscription = [];
	if(!in_array($subscription_id, $customer_subscription_ids)){
		continue;
	}
	if(!empty($_REQUEST['shipdate'])){
		$updated_subscription_res = $rc->post('/subscriptions/'.$subscription_id.'/set_next_charge_date', [
			'date' => date('Y-m-d', strtotime($_REQUEST['shipdate'])),
		]);
		if(empty($updated_subscription_res['subscription'])){
			continue;
		}
		$updated_subscription = $updated_subscription_res['subscription'];
	}
	if(!empty($data)){
		$updated_subscription_res = $rc->put('/subscriptions/'.$subscription_id, $data);
		if(empty($updated_subscription_res['subscription'])){
			continue;
		}
		$updated_subscription = $updated_subscription_res['subscription'];
	}
	if(!empty($updated_subscription)){
		foreach($subscriptions as $index=>$subscription){
			if($subscription['id'] == $updated_subscription['id']){
				$subscriptions[$index] = $updated_subscription;
			}
		}
	}
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
	'success' => true,
	'subscriptions' => group_subscriptions($subscriptions, $addresses),
	'subscriptions_raw' => $subscriptions,
]);