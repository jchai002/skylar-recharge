<?php

require_once(__DIR__.'/../includes/config.php');
require_once(__DIR__.'/../includes/class.ShopifyClient.php');

$sc = new ShopifyClient();

$page_size = 250;
$page = 0;

do {
	$page++;
	$orders = $sc->get("/admin/orders.json", [
		'limit' => $page_size,
		'page' => $page,
		'order' => 'created_at asc',
		'status' => 'any',
	]);

	foreach($orders as $order){
		if($sc->callsLeft() < 5){
			echo $sc->callsLeft()." calls remaining, taking a nap...".PHP_EOL;
			sleep(5);
		}
		echo insert_update_order($db, $order, $sc)." ".$order['created_at'].PHP_EOL;
	}
} while(!empty($orders));

/*
$stmt = $db->query("SELECT o.shopify_id FROM orders o LEFT JOIN order_line_items oli ON o.id=oli.order_id
WHERE o.created_at > '2019-03-01'
AND oli.id IS NULL
ORDER BY o.created_at DESC");

foreach($stmt->fetchAll(PDO::FETCH_COLUMN) as $order_id){
	$order = $sc->get('/admin/orders/'.$order_id.'.json');
	if(!empty($order)){
		echo insert_update_order($db, $order, $sc).PHP_EOL;
	}
}
*/