<?php
require_once(__DIR__.'/../includes/config.php');

$start_time = microtime(true);
$orders = [];
$page = 0;
do {
	$page++;
	$res = $rc->get('/orders', [
		'status' => 'QUEUED',
		'page' => $page,
		'limit' => 250,
	]);
	foreach($res['orders'] as $order){
		foreach($order['line_items'] as $line_item){
			if($line_item['shopify_product_id'] == 3516688433239){
				$orders[] = $order;
				break;
			}
		}
	}
	echo "Adding ".count($res['orders'])." to array - total: ".count($orders).PHP_EOL;
	echo "Rate: ".(count($orders) / (microtime(true) - $start_time))." orders/s".PHP_EOL;
} while(count($res['orders']) == 250);

$start_time = microtime(true);
foreach($orders as $index => $order){
	$order_time = strtotime($order['scheduled_at']);
	if(date('d', $order_time) != 22){
		continue;
	}
	echo "Moving order ".$order['id']." address ".$order['address_id'].": ";
	$res = $rc->post('/orders/'.$order['id'].'/change_date', [
		'scheduled_at' => date('Y-m', $order_time).'-19'
	]);
	echo $res['order']['scheduled_at'].PHP_EOL;
	$index++;
	if($index % 20 == 0){
		echo "Updated: ".$index."/".count($orders)." Rate: ".($index / (microtime(true) - $start_time))." orders/s".PHP_EOL;
	}
}