<?php
require_once(__DIR__.'/../../includes/config.php');

$interval = 5;
$page_size = 250;
$sc = new ShopifyClient();
$min_date = date('Y-m-d H:i:00P', time()-60*6);
$start_time = time();

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


// Daily syncs
if(
	(date('G', $start_time) == 12 && date('i', $start_time) < 4 && !in_array(date('N', $start_time), [6,7]))
	|| (!empty($argv) && !empty($argv[1]) && $argv[1] == 'all')
){
	echo "Updating missing AC fulfillments".PHP_EOL;
	$stmt = $db->query("SELECT o.shopify_id FROM ac_orders aco
		LEFT JOIN order_line_items oli ON aco.order_line_item_id=oli.id
		LEFT JOIN fulfillments f ON f.id=oli.fulfillment_id
		LEFT JOIN orders o ON oli.order_id=o.id
		WHERE oli.fulfillment_id IS NULL
		AND o.created_at < '".date('Y-m-d', $start_time - (24*60*60))."'
		AND o.cancelled_at IS NULL;");
	foreach($stmt->fetchAll(PDO::FETCH_COLUMN) as $order_id){
		echo " - ".$order_id.": ".PHP_EOL;
		$fulfillment_res = $sc->get('/admin/orders/'.$order_id.'/fulfillments.json', [
			'updated_at_min' => $min_date,
			'limit' => $page_size,
			'page' => $page,
		]);
		foreach($fulfillment_res as $fulfillment){
			echo "   - ".insert_update_fulfillment($db, $fulfillment).PHP_EOL;
		}
	}
}