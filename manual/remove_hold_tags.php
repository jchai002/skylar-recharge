<?php
require_once(__DIR__.'/../includes/config.php');

$start_date = '2019-12-03';
$start_time = time();
$page_size = 250;
$total_orders = 0;
$url = "orders.json";
do {
	$orders = $sc->get($url, [
		'created_at_min' => $start_date,
		'limit' => $page_size,
		'order' => 'created_at asc',
	]);
	foreach($orders as $order){
		$total_orders++;
		echo insert_update_order($db, $order, $sc).PHP_EOL;
	}
	$elapsed_time = (time()-$start_time)/60;
	echo "Synced $total_orders in ".round($elapsed_time, 2)."m ".round($total_orders/($elapsed_time), 2)." orders/min".PHP_EOL;
	$url = $sc->last_response_links['next'] ?? false;
} while(!empty($url));

$stmt = $db->query("SELECT shopify_id as id, tags FROM orders WHERE tags LIKE '%HOLD: Test Order%' AND created_at >= '".$start_date."'");

$orders = $stmt->fetchAll();

echo "Starting untagging on ".count($orders)." orders".PHP_EOL;
foreach($orders as $order){

	$tags = explode(', ', $order['tags']);

	$key = array_search('HOLD: Test Order',$tags);
	if (false !== $key) {
		unset($tags[$key]);
	}

	echo $order['id'];
	$res = $sc->put('/admin/orders/'.$order['id'].'.json', ['order' => [
		'id' => $order['id'],
		'tags' => implode(', ', $tags),
	]]);
	insert_update_order($db, $res, $sc);
	echo " ".$res['tags'].PHP_EOL;
}
