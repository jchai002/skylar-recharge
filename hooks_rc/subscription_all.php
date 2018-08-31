<?php
require_once('../includes/config.php');
require_once('../includes/class.RechargeClient.php');

$rc = new RechargeClient();
// get $charge from webhook
if(!empty($_REQUEST['id'])){
	// cheaty since we're just using it to look up the charge
	$subscription = ['id' => $_REQUEST['id']];
} else {
	$data = file_get_contents('php://input');
	if(!empty($data)){
		$res = json_decode($data);
	}
	if(empty($res['subscription'])){
		exit;
	}
	$subscription = $res['subscription'];
}

// Get next charge for this subscription
$res = $rc->get('/charges', [
	'subscription_id' => $subscription['id'],
	'status' => 'QUEUED',
]);
if(empty($res['charges'])){
	exit;
}
$charges = $res['charges'];

foreach($charges as $charge){
	if($charge['status'] != 'QUEUED'){
		exit;
	}
	foreach($charge['line_items'] as $line_item){
		// don't do it for old sample products
		if(in_array($line_item['shopify_product_id'], [738567323735, 738567520343, 738394865751])){
			exit;
		}
	}

	$discount_factors = calculate_discount_factors($charge);
	var_dump($discount_factors);
	$discount_amount = calculate_discount_amount($charge, $discount_factors);
	var_dump($discount_amount);

	$code = get_charge_discount_code($db, $rc, $discount_amount);
	var_dump($code);
	apply_discount_code($rc, $charge, $code);
}