<?php

global $rc;

$new_shipping_lines = !empty($_REQUEST['expedited']) ? null : [
	[
		'price' => '8.00',
		'title' => 'Member-only Expedited Shipping',
		'code' => 'US 2 Day',
	]
];

$res = $rc->put('addresses/'.$address_id, [
	'shipping_lines_override' => $new_shipping_lines
]);

if(!empty($res['error'])){
	echo json_encode([
		'new_shipping_lines' => $new_shipping_lines,
		'success' => false,
		'res' => $res,
		'error' => $res['error']
	]);
} else {
	echo json_encode([
		'address' => $res['address'],
		'success' => true,
		'res' => $res,
	]);
}