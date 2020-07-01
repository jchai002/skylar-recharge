<?php

use GuzzleHttp\Handler\CurlMultiHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\ResponseInterface;

require_once(__DIR__.'/../includes/config.php');

$sc_product = sc_get_monthly_scent_public($db);
print_r($sc_product);
if(
	(
		time() < offset_date_skip_weekend(strtotime($sc_product['ship_date'])) - 7*60*60 // Hold until 5 pm the day before
		|| time() > strtotime($sc_product['ship_date'])
	)
	&& (empty($argv[1]) || $argv[1] != 'force')
){
	die('Today is not the day to untag!');
}
/*
$today = date('Y-m-d');
// First, make sure we are fully synced
$page = 0;
$page_size = 250;
$total_orders = 0;
$start_time = time();
$url = 'orders.json';
do {
	$orders = $sc->get($url, [
		'created_at_min' => $today,
		'limit' => $page_size,
		'order' => 'created_at asc',
	]);
	$url = $sc->last_response_links['next'] ?? false;
	foreach($orders as $order){
		$total_orders++;
		echo insert_update_order($db, $order, $sc).PHP_EOL;
	}
	$elapsed_time = (time()-$start_time)/60;
	echo "Synced $total_orders in ".round($elapsed_time, 2)."m ".round($total_orders/($elapsed_time), 2)." orders/min".PHP_EOL;
} while(!empty($url));
*/

$stmt = $db->query("SELECT shopify_id as id, tags FROM orders WHERE tags LIKE '%HOLD: Scent Club Blackout%'");

$orders = $stmt->fetchAll();

if(empty($orders)){
	echo "No orders to untag!".PHP_EOL;
	exit;
}

echo "Starting untagging on ".count($orders)." orders".PHP_EOL;

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
		}
	}
	$promises = array_values(array_filter($promises));
	// Refill
	while(!empty($orders) && count($promises) < $max_concurrent && $sc->totalCallsLeft() > 0){
		$order = array_pop($orders);

		$tags = explode(', ', $order['tags']);
		$key = array_search('HOLD: Scent Club Blackout',$tags);
		if (false !== $key) {
			unset($tags[$key]);
		}
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
			if($e->getCode() == 429){
				echo "Got 429 error, sleeping".PHP_EOL;
				// TODO: Re-add request
				sleep(4);
				return;
			}
			print_r($e->getCode());
			print_r($e->getMessage());
			die();
		});
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
echo "Finished untagging $orders_processed orders in $time_elapsed s, ".round($orders_processed/$time_elapsed, 2)." orders/s".PHP_EOL;

// TODO: insert_update order
// TODO: Create loop that re-checks
