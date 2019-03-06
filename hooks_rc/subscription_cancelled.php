<?php
require_once('../includes/config.php');

$rc = new RechargeClient();
// get $charge from webhook
if(!empty($_REQUEST['id'])){
	$res = $rc->get('/subscriptions/'.$_REQUEST['id']);
} else {
	$data = file_get_contents('php://input');
	log_event($db, 'webhook', $data, 'subscription_cancelled');
	if(!empty($data)){
		$res = json_decode($data, true);
	}
}
$subscription = $res['subscription'];
var_dump($subscription);

$product = get_product($db, $subscription['shopify_product_id']);
if(!is_scent_club($product)){
	die();
}
$stmt = $db->prepare("SELECT * FROM sc_product_info WHERE sc_date = ?");

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