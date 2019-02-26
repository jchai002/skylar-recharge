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
	$charge = $charges[0];
} else {
	$charge = $rc->get('/charges/'.intval($_REQUEST['charge_id']));
}

$product = $sc->get("/admin/products/".intval($_REQUEST['product_id'].'.json'));
foreach($product['variants'] as $variant){
	if($variant['id'] == $_REQUEST['variant_id']){
		break;
	}
}

$res = $rc->post('/addresses/'.$charge['address_id'].'/onetime', [
	'address_id' => $charge['address_id'],
	'next_charge_scheduled_at' => $charge['scheduled_at'],
	'product_title' => $product['title'],
	'price' => round($variant['price']*.9, 2),
	'quantity' => 1,
	'shopify_variant_id' => $variant['id'],
]);

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