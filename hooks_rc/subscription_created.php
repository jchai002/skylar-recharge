<?php
require_once(__DIR__.'/../includes/config.php');

// get $charge from webhook
if(!empty($_REQUEST['id'])){
	$res = $rc->get('/subscriptions/'.$_REQUEST['id']);
} else {
	respondOK();
	$data = file_get_contents('php://input');
	if(!empty($data)){
		$res = json_decode($data, true);
	}
	log_event($db, 'webhook', $res['subscription']['id'], 'subscription_created', $data);
}
$subscription = $res['subscription'];
var_dump($subscription);

try {
	insert_update_rc_subscription($db, $subscription, $rc, $sc);
} catch(\Throwable $e){
	log_event($db, 'EXCEPTION', json_encode([$e->getLine(), $e->getFile(), $e->getCode(), $e->getMessage(), $e->getTraceAsString()]), 'subscription_created_insert', json_encode($subscription), '', 'webhook');
}

if($subscription['status'] != 'ACTIVE'){
	die();
}

$product = get_product($db, $subscription['shopify_product_id']);
if(!is_scent_club($product)){
	die();
}

$res = $rc->get('/customers/'.$subscription['customer_id']);
$shopify_customer_id = $res['customer']['shopify_customer_id'];
$shopify_customer = $sc->get('customers/'.$shopify_customer_id.'.json');

try {
	$res = $sc->post('/admin/customers/'.$shopify_customer_id.'/metafields.json', ['metafield'=> [
		'namespace' => 'scent_club',
		'key' => 'active',
		'value' => 1,
		'value_type' => 'integer'
	]]);
	print_r($res);
	$tags = explode(', ',$shopify_customer['tags']);
	if(!in_array('Scent Club Member', $tags)){
		$tags[] = 'Scent Club Member';
		$shopify_customer = $sc->put('/admin/customers/'.$shopify_customer_id.'.json', ['customer' => [
			'id' => $shopify_customer_id,
			'tags' => implode(', ', $tags),
		]]);
		insert_update_customer($db, $shopify_customer);
	}
} catch(\GuzzleHttp\Exception\ClientException $e){
	log_event($db, 'API_ERROR', json_encode($e->getResponse()), 'POST customers/'.$shopify_customer_id.'/metafields.json', json_encode(['metafield'=> [
		'namespace' => 'scent_club',
		'key' => 'active',
		'value' => 1,
		'value_type' => 'integer'
	]]), '', 'subscription_created');
	var_dump($e->getResponse());
} catch(\Throwable $e){
	log_event($db, 'API_ERROR', json_encode([$e->getLine(), $e->getFile(), $e->getCode(), $e->getMessage(), $e->getTraceAsString()]), 'POST customers/'.$shopify_customer_id.'/metafields.json', json_encode(['metafield'=> [
		'namespace' => 'scent_club',
		'key' => 'active',
		'value' => 1,
		'value_type' => 'integer'
	]]), '', 'subscription_created');
	var_dump($e);
}
