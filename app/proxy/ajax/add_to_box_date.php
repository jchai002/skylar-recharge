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

$product = $sc->get("/admin/products/".intval($_REQUEST['product_id']).'.json');
foreach($product['variants'] as $variant){
	if($variant['id'] == $_REQUEST['variant_id']){
		break;
	}
}

$res = $rc->post('/addresses/'.$main_sub['address_id'].'/onetimes', [
	'address_id' => $main_sub['address_id'],
	'next_charge_scheduled_at' => date('Y-m-d', $_REQUEST['ship_time']),
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