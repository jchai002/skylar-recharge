<?php

global $db;
$rc = new RechargeClient();
$sc = new ShopifyClient();

$main_sub = sc_get_main_subscription($db, $rc, [
	'status' => 'ACTIVE',
	'shopify_customer_id' => $_REQUEST['c'],
]);

$stmt = $db->prepare("SELECT p.shopify_id FROM skylar.products p
LEFT JOIN variants v ON p.id=v.product_id
WHERE v.shopify_id=?");
$stmt->execute([$_REQUEST['variant_id']]);

$product_id = $stmt->fetchColumn();

$product = $sc->get("/admin/products/$product_id.json");
$product['type'] = $product['product_type'];
foreach($product['variants'] as $variant){
	if($variant['id'] == $_REQUEST['variant_id']){
		break;
	}
}

$frequency = empty($_REQUEST['frequency']) ? 'onetime' : $_REQUEST['frequency'];

if(is_scent_club_month($product)){
	$price = $variant['price'];
	$product['title'] = 'Skylar Scent Club';
} else {
	$price = round($variant['price']*.9, 2);
}
$res_id = false;
if(!is_numeric($frequency) || $frequency < 1 || $frequency > 12){
	$res = $rc->post('/addresses/'.$main_sub['address_id'].'/onetimes', [
		'address_id' => $main_sub['address_id'],
		'next_charge_scheduled_at' => date('Y-m-d', $_REQUEST['ship_time']),
		'product_title' => $product['title'],
		'price' => $price,
		'quantity' => 1,
		'shopify_variant_id' => $variant['id'],
	]);
	$res_id = $res['onetime']['id'];
} else {
	$res = $rc->post('/addresses/'.$main_sub['address_id'].'/subscriptions', [
		'address_id' => $main_sub['address_id'],
		'next_charge_scheduled_at' => date('Y-m-d', $_REQUEST['ship_time']),
		'product_title' => $product['title'],
		'price' => $price,
		'quantity' => 1,
		'shopify_variant_id' => $variant['id'],
		'order_interval_unit' => 'month',
		'order_interval_frequency' => $frequency,
		'charge_interval_frequency' => $frequency,
		'order_day_of_month' => $main_sub['order_day_of_month'],
	]);
	$res_id = $res['subscription']['id'];
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
		'id' => $res_id,
		'res' => $res,
		'product' => $product,
		'sc_month' => is_scent_club_month($product),
	]);
}