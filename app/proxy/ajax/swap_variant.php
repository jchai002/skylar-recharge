<?php
header('Content-Type: application/json');

global $db, $rc;

if(empty($_REQUEST['subscription_id']) || empty($_REQUEST['variant_id'])){
	die(json_encode([
		'success' => false,
		'errors' => [['msg'=>'Fields Missing']],
	]));
}

$subscription_id = intval($_REQUEST['subscription_id']);
$variant_id = intval($_REQUEST['variant_id']);

$variant = get_variant($db, $variant_id);
$subscription = get_rc_subscription($db, $subscription_id, $rc, $sc);

if($subscription['status'] == 'ONETIME'){
	$res = $rc->put('/onetimes/'.$subscription_id, [
		'product_title' => $variant['product_title'],
		'shopify_variant_id' => $variant['shopify_id'],
	]);
	if(!empty($res['onetime'])){
		insert_update_rc_subscription($db, $res['onetime'], $rc, $sc);
	}
} else {
	$res = $rc->put('/subscriptions/'.$subscription_id, [
		'product_title' => $variant['product_title'],
		'shopify_variant_id' => $variant['shopify_id'],
	]);
	if(!empty($res['subscription'])){
		insert_update_rc_subscription($db, $res['subscription'], $rc, $sc);
	}
}


echo json_encode([
	'success' => true,
	'res' => $res,
]);