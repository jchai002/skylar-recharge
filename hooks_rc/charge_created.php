<?php
require_once('../includes/config.php');
require_once('../includes/class.RechargeClient.php');



$rc = new RechargeClient();
// get $charge from webhook
if(!empty($_REQUEST['id'])){
	$res = $rc->get('/charges/'.$_REQUEST['id']);
} else {
	$data = file_get_contents('php://input');
	if(!empty($data)){
		$res = json_decode($data);
	}
}
if(empty($res['charge'])){
	exit;
}
$charge = $res['charge'];


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

// TODO: Need to handle tracking sample discount
$code = get_charge_discount_code($db, $rc, $discount_amount);
var_dump($code);
apply_discount_code($rc, $charge, $code);