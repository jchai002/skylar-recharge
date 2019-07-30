<?php
http_response_code(200);
require_once('../includes/config.php');

$sc = new ShopifyClient();

if(!empty($_REQUEST['id'])){
	$fulfillments = $sc->get('/admin/orders/'.intval($_REQUEST['id']).'/fulfillments.json');
} else {
	$data = file_get_contents('php://input');
	$fulfillments = [json_decode($data, true)];
	log_event($db, 'webhook', $fulfillments[0]['id'], 'fulfillment_created', $data);
}

if(empty($fulfillments) || empty($fulfillments[0]) || empty($fulfillments[0]['id'])){
	die('No data');
}
foreach($fulfillments as $fulfillment){
	$fulfillment_id = insert_update_fulfillment($db, $fulfillment);
	$tracker = \EasyPost\Tracker::create([
		'tracking_code' => $fulfillment['tracking_number'],
		'carrier' => $fulfillment['tracking_company'],
	]);
	var_dump($tracker);
}