<?php

global $rc;

$new_shipping_lines = empty($_REQUEST['expedited']) ? null : [
	[
		'price' => '8.00',
		'title' => 'Members-only Expedited Shipping',
		'code' => 'US 2 Day',
	]
];

$res = $rc->put('addresses/'.$address_id, [
	'shipping_lines_override' => $new_shipping_lines,
	'commit_update' => true,
]);
$address = $res['address'];
$charge = null;
if(!empty($_REQUEST['charge_id'])){
	$res = $rc->get('charges/'.intval($_REQUEST['charge_id']));
	if(!empty($res['charge'])){
		$res = $rc->post('charges/'.$res['charge']['id'].'/change_next_charge_date', [
			'next_charge_date' => $res['charge']['scheduled_at'],
		]);
	}
}
if(empty($charge)){
	sleep(6);
}

if(!empty($res['error'])){
	echo json_encode([
		'new_shipping_lines' => $new_shipping_lines,
		'success' => false,
		'res' => $res,
		'error' => $res['error']
	]);
} else {
	echo json_encode([
		'address' => $address,
		'charge' => $charge,
		'success' => true,
		'res' => $res,
	]);
}