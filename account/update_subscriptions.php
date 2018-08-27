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

foreach($subscription_ids as $subscription_id){
	if(!in_array($subscription_id, $customer_subscription_ids)){
		continue;
	}
	if(!empty($_REQUEST['shipdate'])){
		$rc->post('/subscriptions/'.$subscription_id.'/set_next_charge_date', [
			'date' => date('Y-m-d', strtotime($_REQUEST['shipdate'])),
		]);
	}
	$data = [];
	if(!empty($_REQUEST['frequency'])){
		$data['order_interval_frequency'] = $data['charge_interval_frequency'] = intval($_REQUEST['frequency']);
	}
	if(!empty($_REQUEST['quantity'])){
		$data['quantity'] = intval($_REQUEST['quantity']);
		// May need to update price as well here
	}
	if(!empty($data)){
		$rc->put('/subscriptions/'.$subscription_id, $data);
	}
}