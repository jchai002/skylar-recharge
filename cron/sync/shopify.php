<?php
require_once(__DIR__.'/../../includes/config.php');

$interval = 5;
$page_size = 250;
$sc = new ShopifyClient();
$min_date = date('Y-m-d H:i:00P', time()-60*6);

// Products
echo "Updating products".PHP_EOL;
$page = 0;
do {
	$page++;
	$res = $sc->get('/admin/products.json', [
		'updated_at_min' => $min_date,
		'limit' => $page_size,
		'page' => $page,
	]);
	foreach($res as $product){
		echo insert_update_product($db, $product).PHP_EOL;
	}
} while(count($res) >= $page_size);

// Customers
echo "Updating customers".PHP_EOL;
$page = 0;
do {
	$page++;
	$res = $sc->get('/admin/customers.json', [
		'updated_at_min' => $min_date,
		'limit' => $page_size,
		'page' => $page,
	]);
	foreach($res as $customer){
		echo insert_update_customer($db, $customer).PHP_EOL;
	}
} while(count($res) >= $page_size);

// Orders
echo "Updating orders and fulfillments".PHP_EOL;
$page = 0;
do {
	$page++;
	$res = $sc->get('/admin/orders.json', [
		'updated_at_min' => $min_date,
		'limit' => $page_size,
		'page' => $page,
	]);
	foreach($res as $order){
		echo insert_update_order($db, $order, $sc).PHP_EOL;
		$fulfillment_res = $sc->get('/admin/orders/'.$order['id'].'/fulfillments.json', [
			'updated_at_min' => $min_date,
			'limit' => $page_size,
			'page' => $page,
		]);
		foreach($fulfillment_res as $fulfillment){
			echo " - ".insert_update_fulfillment($db, $fulfillment).PHP_EOL;
		}
	}
} while(count($res) >= $page_size);