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

$reason = empty($_REQUEST['reason']) ? 'N/A' : $_REQUEST['reason'];

$address_id = 0;
foreach($subscription_ids as $subscription_id){
	$updated_subscription = [];
	if(!in_array($subscription_id, $customer_subscription_ids)){
		continue;
	}

	$updated_subscription_res = $rc->post('/subscriptions/'.$subscription_id.'/activate', ['status' => 'ACTIVE']);
	if(empty($updated_subscription_res['subscription'])){
		continue;
	}
	$updated_subscription = $updated_subscription_res['subscription'];
	$next_charge_time = strtotime($updated_subscription['next_charge_scheduled_at']);
	if(empty($next_charge_time) || $next_charge_time < time()){
		// Fix for recharge bug where next charge time can be null
		$next_charge_time = offset_date_skip_weekend(strtotime('+17 days'));
		$res = $rc->post('/subscriptions/'.$subscription_id.'/set_next_charge_date', ['date' => date('Y-m-d')]);
		if(!empty($res['subscription'])){
			$updated_subscription = $res['updated_subscription'];
		}
	}

	foreach($subscriptions as $index => $subscription){
		if($subscription['id'] == $updated_subscription['id']){
			$subscriptions[$index] = $updated_subscription;
			$address_id = $updated_subscription['address_id'];
			break;
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