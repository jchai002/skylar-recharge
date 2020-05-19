<?php
require_once(__DIR__.'/../includes/config.php');

$number_to_unhold = 3900;

$total_quantity = 0;
$page = 0;
$page_size = 250;
$total_orders = 0;
$start_time = time();
$url = 'orders.json';
$all_orders = [];
$outstream = fopen("unhold.csv", 'w');
$csv_labels = [
	'id' => 'id',
	'created_at' => 'created_at',
	'name' => 'name',
];
fputcsv($outstream, $csv_labels);
do {
	$orders = $sc->get($url, [
		'created_at_min' => '2020-04-14',
		'limit' => $page_size,
		'order' => 'created_at asc',
	]);
	$url = $sc->last_response_links['next'] ?? false;
	foreach($orders as $order){
		$order_quantity = array_reduce($order['line_items'], function($carry, $line_item) use($db) {
			$hand_sani = in_array('Hand Sanitizer', get_product($db, $line_item['product_id'])['tags']);
			return $carry + ($hand_sani ? $line_item['quantity'] : 0);
		}, 0);
		$total_orders++;
		if($order_quantity < 1){
			continue;
		}
		if($total_quantity + $order_quantity > $number_to_unhold){
			break 2;
		}
		$total_quantity += $order_quantity;
		$all_orders[] = $order;
		fputcsv($outstream, array_intersect_key($order, $csv_labels));
		echo $order['name']." - ".$total_quantity.PHP_EOL;
		if($total_quantity >= $number_to_unhold){
			break 2;
		}
	}
	$elapsed_time = (time()-$start_time)/60;
	echo "Synced $total_orders in ".round($elapsed_time, 2)."m ".round($total_orders/($elapsed_time), 2)." orders/min".PHP_EOL;
} while(!empty($url));
