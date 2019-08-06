<?php

global $db, $rc;

if(empty($_REQUEST['charge_id'])){
	$res = $rc->get('/customers', [
		'shopify_customer_id' => $_REQUEST['c'],
	]);
	$customer = $res['customers'][0];
	$res = $rc->get('/charges', [
		'customer_id' => $customer['id'],
		'status' => 'QUEUED',
	]);
	$charges = $res['charges'];
	usort($charges, function ($item1, $item2) {
		if (strtotime($item1['scheduled_at']) == strtotime($item2['scheduled_at'])) return 0;
		return strtotime($item1['scheduled_at']) < strtotime($item2['scheduled_at']) ? -1 : 1;
	});
	$charge = $charges[0];
} else {
	$res = $rc->get('/charges/'.intval($_REQUEST['charge_id']));
	$charge = $res['charge'];
}
//var_dump($charge);
$sc = new ShopifyClient();
$product = $sc->get("/admin/products/".intval($_REQUEST['product_id']).'.json');
foreach($product['variants'] as $variant){
	if($variant['id'] == $_REQUEST['variant_id']){
		break;
	}
}

$frequency = empty($_REQUEST['frequency']) ? 'onetime' : $_REQUEST['frequency'];
$res_id = false;
if(is_scent_club_month($product)){
	$price = $variant['price'];
	$product['title'] = 'Skylar Scent Club';
} else {
	$price = round($variant['price']*.9, 2);
}
if(!is_numeric($frequency) || $frequency < 1 || $frequency > 12){
	$res = $rc->post('/addresses/'.$charge['address_id'].'/onetimes', [
		'address_id' => $charge['address_id'],
		'next_charge_scheduled_at' => $charge['scheduled_at'],
		'product_title' => $product['title'],
		'price' => $price,
		'quantity' => 1,
		'shopify_variant_id' => $variant['id'],
	]);
	if(!empty($res['onetime'])){
		$res_id = $res['onetime']['id'];
	}
} else {
	$main_sub = sc_get_main_subscription($db, $rc, [
		'address_id' => $charge['address_id'],
	]);
	$res = $rc->post('/addresses/'.$charge['address_id'].'/subscriptions', [
		'address_id' => $charge['address_id'],
		'next_charge_scheduled_at' => $charge['scheduled_at'],
		'product_title' => $product['title'],
		'price' => $price,
		'quantity' => 1,
		'shopify_variant_id' => $variant['id'],
		'order_interval_unit' => 'month',
		'order_interval_frequency' => $frequency,
		'charge_interval_frequency' => $frequency,
		'order_day_of_month' => $main_sub['order_day_of_month'],
	]);
	if(!empty($res['subscription'])){
		$res_id = $res['subscription']['id'];
	}
}

if(!empty($res['error'])){
	echo json_encode([
		'success' => false,
		'error' => $res['error'],
		'res' => $res,
	]);
} else {
	echo json_encode([
		'success' => true,
		'res' => $res,
		'id' => $res_id,
	]);
}