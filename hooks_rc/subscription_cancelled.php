<?php
require_once('../includes/config.php');

$rc = new RechargeClient();
// get $charge from webhook
if(!empty($_REQUEST['id'])){
	$res = $rc->get('/subscriptions/'.$_REQUEST['id']);
} else {
	$data = file_get_contents('php://input');
	if(!empty($data)){
		$res = json_decode($data, true);
	}
	log_event($db, 'webhook', $res['subscription']['id'], 'subscription_cancelled', $data);
}
$subscription = $res['subscription'];
var_dump($subscription);

if(empty($subscription['address_id'])){
	// Deleted
	$stmt = $db->prepare("UPDATE rc_subscriptions SET deleted_at=:deleted_at WHERE recharge_id=:recharge_id");
	$stmt->execute([
		'deleted_at' => date('Y-m-d H:i:s'),
		'recharge_id' => $subscription['id'],
	]);
	die();
}

$product = get_product($db, $subscription['shopify_product_id']);
if(!is_scent_club($product)){
	die();
}

// Remove any scent club onetimes
$res = $rc->get('/onetimes', [
	'address_id' => $subscription['address_id'],
]);
if(!empty($res['onetimes'])){
	foreach($res['onetimes'] as $onetime){
		if($onetime['status'] != 'ONETIME'){
			continue; // Fix for api bug
		}
		$onetime_product = get_product($db, $onetime['shopify_product_id']);
		if(!is_scent_club_any($onetime_product)){
			continue;
		}
		$rc->delete('/onetimes/'.$onetime['id']);
	}
}

$res = $rc->get('/customers/'.$subscription['customer_id']);
$customer = $res['customer'];

$sc = new ShopifyClient();
$res = $sc->post('/admin/customers/'.$customer['shopify_customer_id'].'/metafields.json', ['metafield'=> [
	'namespace' => 'scent_club',
	'key' => 'active',
	'value' => 0,
	'value_type' => 'integer'
]]);