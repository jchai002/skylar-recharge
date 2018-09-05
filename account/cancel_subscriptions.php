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
$res = $rc->get('/subscriptions', [
	'shopify_customer_id' => $_REQUEST['customer_id'],
]);
if(empty($res['subscriptions'])){
	die(json_encode($res));
}
$subscriptions = $res['subscriptions'];
$address_id = $res['subscriptions'][0]['address_id'];
$customer_id = $res['subscriptions'][0]['customer_id'];
$subscriptions_by_id = [];
foreach($res['subscriptions'] as $subscription){
	$subscriptions_by_id[$subscription['id']] = $subscription;
}

$reason = empty($_REQUEST['reason']) ? 'N/A' : $_REQUEST['reason'];

$address_id = 0;
foreach($subscription_ids as $subscription_id){
	$updated_subscription = [];
	if(!array_key_exists($subscription_id, $subscriptions_by_id)){
		continue;
	}

	$subscription = $subscriptions_by_id[$subscription_id];
	$address_id = $subscription['address_id'];
	$customer_id = $subscription['customer_id'];

	if($subscription['status'] == 'ACTIVE'){
		if($reason == 'Quantity Reduced'){
			$rc->delete('/subscriptions/'.$subscription['id']);
			unset($subscriptions_by_id[$subscription['id']]);
		} else {
			$updated_subscription_res = $rc->post('/subscriptions/'.$subscription_id.'/cancel', ['cancellation_reason' => $reason]);
			if(!empty($updated_subscription_res['subscription'])){
				$subscriptions_by_id[$subscription_id] = $updated_subscription_res['subscription'];
			}
		}
		$subscriptions_by_id[$subscription['id']] = $updated_subscription_res['subscription'];
	} else if($subscription['status'] == 'ONETIME') {
		$rc->delete('/onetimes/'.$subscription['id']);
		unset($subscriptions_by_id[$subscription['id']]);
	}

}

$addresses = [];
$addresses_res = $rc->get('/customers/'.$customer_id.'/addresses');
if(empty($addresses_res['addresses'])){
	die(json_encode($subscriptions_by_id));
}
foreach($addresses_res['addresses'] as $address_res){
	$addresses[$address_res['id']] = $address_res;
}

// Remove sample credit from address
if(!empty($address_id)){
	$cart_attributes = [];
	foreach($addresses[$address_id]['cart_attributes'] as $cart_attribute){
		if($cart_attribute['name'] == '_sample_discount'){
			continue;
		}
		$cart_attributes[] = $cart_attribute;
	}
	$res = $rc->put('/addresses/'.$address_id, [
		'cart_attributes' => $cart_attributes,
	]);
	$addresses[$address_id] = $res['address'];
}

echo json_encode([
	'success' => true,
	'subscriptions' => group_subscriptions($subscriptions_by_id, $addresses),
	'subscriptions_raw' => array_values($subscriptions_by_id),
]);