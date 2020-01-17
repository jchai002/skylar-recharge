<?php

use GuzzleHttp\Handler\CurlMultiHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\PromiseInterface;

require_once(__DIR__.'/includes/config.php');
$tag = "TEST TAG";

$today = date('Y-m-d', strtotime('-3 months'));
// First, make sure we are fully synced
$page = 0;
$page_size = 250;
$start_time = time();
$all_orders = [];
do {
	$page++;
	echo "getting orders...";
	$orders = $sc->get('/admin/orders.json', [
		'created_at_max' => $today,
		'limit' => $page_size,
		'page' => $page,
		'order' => 'created_at desc',
	]);
	echo "got orders".PHP_EOL;
	foreach($orders as $order){
		$all_orders[] = $order;
	}
	$total_orders = count($all_orders);
	$elapsed_time = (time()-$start_time)/60;
	echo "Synced $total_orders in ".round($elapsed_time, 2)."m ".round($total_orders/($elapsed_time), 2)." orders/min".PHP_EOL;
} while($total_orders < 1000);

$orders = $all_orders;

echo "Starting Tagging!".PHP_EOL;

$curl = new CurlMultiHandler();
$handler = HandlerStack::create($curl);
$sc = new ShopifyClient([
	'handler' => $handler,
]);
$sc->rate_buffer = 10;
if(!empty($_ENV['SHOPIFY_UNTAG_KEYS'])){
	$extra_keys = explode(',',$_ENV['SHOPIFY_UNTAG_KEYS']);
	foreach($extra_keys as $extra_key){
		$sc->addSecret(...explode(':', $extra_key));
		echo "Adding Key ".$extra_key.PHP_EOL;
	}
}

$promises = [];
$max_concurrent = 20;
$start_time = time();
$orders_processed = 0;
$last_output_at = 0;
$last_reason_at = 0;
while(!empty($orders) || !empty($promises)){
//	echo "Starting pool loop".PHP_EOL;
	$curl->tick();
	// Handle and empty
	/** @var PromiseInterface $promise */
	foreach($promises as $i=>$promise){
		if($promise->getState() != PromiseInterface::PENDING){
//			echo "Freeing Promise $i ".$promise->getState().PHP_EOL;
			$promises[$i] = false;
			$orders_processed++;
//			usleep(100*1000);
		}
	}
	$promises = array_values(array_filter($promises));
	// Refill
	while(!empty($orders) && count($promises) < $max_concurrent && $sc->totalCallsLeft() > 0){
		$order = array_pop($orders);

		$tags = explode(', ', $order['tags']);
		$tags[] = $tag;
		echo "Sending request for ".$order['id'].PHP_EOL;
		$promises[] = $sc->putAsync('orders/'.$order['id'].'.json', ['order' => [
			'id' => $order['id'],
			'tags' => implode(', ', $tags),
		]])->then(function(ShopifyResponse $response) use($db, $sc) {
			$order = $response->getJson();
			if(!empty($order)){
				echo "Processing response for ".$order['id'].PHP_EOL;
//				insert_update_order($db, $order, $sc);
			}
		}, function(Exception $e){
			print_r($e->getMessage());
			die();
		});
//		usleep(100*1000);
	}
	if($last_reason_at != $orders_processed){
		if(empty($sc->totalCallsLeft())){
			$last_reason_at = $orders_processed;
			echo "No calls left".PHP_EOL;
		}
		if(count($promises) >= $max_concurrent){
			$last_reason_at = $orders_processed;
			echo "Hit max concurrent".PHP_EOL;
		}
	}
	usleep(50*1000);
	if($orders_processed != $last_output_at && $orders_processed % 20 == 0){
		$last_output_at = $orders_processed;
		$time_elapsed = time() - $start_time;
		echo "Tagged $orders_processed in $time_elapsed s, ".round($orders_processed/$time_elapsed, 2)." orders/s".PHP_EOL;
	}
}

$time_elapsed = time() - $start_time;
echo "Finished tagging $orders_processed orders in $time_elapsed s, ".round($orders_processed/$time_elapsed, 2)." orders/s".PHP_EOL;