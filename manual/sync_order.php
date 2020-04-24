<?php

require_once(__DIR__.'/../includes/config.php');

$page_size = 250;

$retry = 0;
$url = 'orders.json';
do {
	$orders = $sc->get($url, [
		'limit' => $page_size,
		'order' => 'created_at desc',
		'status' => 'any',
		'created_at_min' => '2020-04-14',
	]);
	if($orders === false){
		if($retry >= 3){
			print_r($sc->last_error);
			break;
		} else {
			$page--;
			$retry++;
			print_r($sc->last_error);
			sleep(5);
			continue;
		}
	}
	$retry = 0;

	foreach($orders as $order){
		if($sc->callsLeft() < 5){
			echo $sc->callsLeft()." calls remaining, taking a nap...".PHP_EOL;
			sleep(5);
		}
		echo insert_update_order($db, $order, $sc)." ".$order['created_at'].PHP_EOL;
	}
	$url = $sc->last_response_links['next'] ?? false;
} while(!empty($url));