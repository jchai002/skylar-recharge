<?php

$rc = new RechargeClient();

$discount_code = empty($_REQUEST['discount_code']) ? '' : $_REQUEST['discount_code'];
$discount_code = strtoupper(trim($discount_code));

if(empty($discount_code)){
	$res = $rc->post('/addresses/'.intval($_REQUEST['address_id']).'/remove_discount');
	if(!empty($res['error'])){
		$res = $rc->post('/charges/'.intval($_REQUEST['charge_id']).'/remove_discount');
	}
} else if(in_array($discount_code, [
	'TRYSCENTCLUB',
	'SCENTCLUBNYC',
	'SURPRISE15',
	'MY50',
	'MYTREAT20',
	'BOXING20',
	'JOLLY20',
	'GOODTRADE',
	'MERRY30',
	'GIFT25',
])){
	$res = ['error' => 'Invalid discount code.'];
} else {
	$res = $rc->post('/addresses/'.intval($_REQUEST['address_id']).'/remove_discount');
	$res = $rc->post('/charges/'.intval($_REQUEST['charge_id']).'/apply_discount', [
		'discount_code' => $discount_code,
	]);
}
if(!empty($res['error'])){
	echo json_encode([
		'discount_code' => $discount_code,
		'success' => false,
		'res' => $res,
		'error' => $res['error']
	]);
} else {
	echo json_encode([
		'discount_code' => $discount_code,
		'success' => true,
		'res' => $res,
	]);
}