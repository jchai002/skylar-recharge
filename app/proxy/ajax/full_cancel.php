<?php

global $db, $rc;

$res = $rc->get('/subscriptions/',[
	'shopify_customer_id' => $_REQUEST['c'],
	'status' => 'active',
]);
$cancel_res = [];
foreach($res['subscriptions'] as $subscription){
	if(!is_scent_club_any(get_product($db, $subscription['shopify_product_id']))){
		continue;
	}
	$cancel_res[] = $this_res = $rc->post('/subscriptions/'.$subscription['id'].'/cancel',[
		'cancellation_reason' => $_REQUEST['reason'],
	]);
	log_event($db, 'SUBSCRIPTION', $subscription['id'], 'CANCEL', $_REQUEST['reason'], 'Cancelled via user account: '.json_encode([$subscription,$this_res]), 'Customer');
}

$res = $rc->get('/onetimes/',[
	'shopify_customer_id' => $_REQUEST['c'],
	'status' => 'active',
]);
$cancel_res = [];
foreach($res['onetimes'] as $subscription){
	$cancel_res[] = $rc->delete('/onetimes/'.$subscription['id']);
}

if(!empty($res['error'])){
	echo json_encode([
		'success' => false,
		'res' => $res['error'],
	]);
} else {
	echo json_encode([
		'success' => true,
		'res' => $cancel_res,
	]);
}