<?php
require_once(__DIR__.'/../includes/config.php');

$today = date('Y-m-d');
// First, make sure we are fully synced
$page = 0;
$page_size = 250;
$total_orders = 0;
$start_time = time();
$orders_by_shipping_method = [];
$count_by_method = [];
$empty_orders = [];
do {
	$page++;
	$orders = $sc->get('/admin/orders.json', [
		'created_at_min' => $today,
		'limit' => $page_size,
		'page' => $page,
		'order' => 'created_at asc',
	]);
	foreach($orders as $order){
		$total_orders++;
		if(empty($order['shipping_lines']) || empty($order['shipping_lines'][0]['code'])){
			$empty_orders[] = $order['id'];
			$method = "";
		} else {
			$method = $order['shipping_lines'][0]['code'];
		}
		if(!array_key_exists($method, $orders_by_shipping_method)){
			$orders_by_shipping_method[$method] = [];
			$count_by_method[$method] = 0;
		}
		$orders_by_shipping_method[$method][] = $order['id'];
		$count_by_method[$method]++;
	}
	$elapsed_time = (time()-$start_time)/60;
	echo "Pulled $total_orders in ".round($elapsed_time, 2)."m ".round($total_orders/($elapsed_time), 2)." orders/min".PHP_EOL;
	echo print_r($count_by_method, true).PHP_EOL;
} while(count($orders) >= $page_size);
unset($orders_by_shipping_method['Standard Weight-based']);
unset($orders_by_shipping_method['PASDDP']);
unset($orders_by_shipping_method['US Next Day']);
unset($orders_by_shipping_method['US 2 Day']);

$outstream = fopen("outlier_shipping_codes.csv", 'w');
fputcsv($outstream, ['order_id', 'shipping_code', 'admin_url']);
foreach($orders_by_shipping_method as $shipping_code => $orders){
	foreach($orders as $order){
		fputcsv($outstream, [$order['id'], $order['name'], $shipping_code, "https://maven-and-muse.myshopify.com/admin/orders/".$order['id']]);
	}
}

print_r($orders_by_shipping_method);