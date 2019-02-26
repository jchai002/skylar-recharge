<?php

$rc = new RechargeClient();

if(empty($_REQUEST['charge_id'])){
	$res = $rc->get('/customers', [
		'shopify_customer_id' => $_REQUEST['c'],
	]);
	$customer = $res['customers'][0];
	$res = $rc->get('/charges', [
		'customer_id' => $customer['id'],
	]);
	$charges = $res['charges'];
	usort($charges, function ($item1, $item2) {
		if (strtotime($item1['scheduled_at']) == strtotime($item2['scheduled_at'])) return 0;
		return strtotime($item1['scheduled_at']) < strtotime($item2['scheduled_at']) ? -1 : 1;
	});
}

if(!empty($res['error'])){
	echo json_encode([
		'success' => false,
		'res' => $res['error'],
	]);
} else {
	echo json_encode([
		'success' => true,
		'res' => $res,
	]);
}