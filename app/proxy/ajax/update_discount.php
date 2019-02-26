<?php

if(empty($_REQUEST['charge_id'])){
	echo "No Charge ID";
}

$rc = new RechargeClient();

if(!empty($_REQUEST['discount_code'])){
	$res = $rc->post('/charges/'.intval($_REQUEST['charge_id']).'/apply_discount', [
		'discount_code' => $_REQUEST['discount_code'],
	]);
} else {
	$res = $rc->post('/charges/'.intval($_REQUEST['charge_id']).'/remove_discount');
}

echo json_encode([
	'success' => true,
	'res' => $res,
]);