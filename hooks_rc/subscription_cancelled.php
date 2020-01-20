<?php
require_once('../includes/config.php');

$rc = new RechargeClient();
// get $charge from webhook
if(!empty($_REQUEST['id'])){
	$res = $rc->get('/subscriptions/'.$_REQUEST['id']);
} else {
	respondOK();
	$data = file_get_contents('php://input');
	if(!empty($data)){
		$res = json_decode($data, true);
	}
	log_event($db, 'webhook', $res['subscription']['id'], 'subscription_cancelled', $data);
}
$subscription = $res['subscription'];
var_dump($subscription);

if(empty($subscription['created_at'])){
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
	die("not sc");
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
$shopify_customer_id = $res['customer']['shopify_customer_id'];
$shopify_customer = $sc->get('/admin/customers/'.$shopify_customer_id.'.json');
$tags = explode(', ',$shopify_customer['tags']);

$sc = new ShopifyClient();
$res = $sc->post('/admin/customers/'.$shopify_customer_id.'/metafields.json', ['metafield'=> [
	'namespace' => 'scent_club',
	'key' => 'active',
	'value' => 0,
	'value_type' => 'integer'
]]);
print_r($tags);
if(in_array('Scent Club Member', $tags)){
	$key = array_search('Scent Club Member', $tags);
	if (false !== $key) {
		unset($tags[$key]);
	}
	print_r($tags);
	$shopify_customer = $sc->put('/admin/customers/'.$shopify_customer_id.'.json', ['customer' => [
		'id' => $shopify_customer_id,
		'tags' => implode(', ', $tags),
	]]);
	insert_update_customer($db, $shopify_customer);
}