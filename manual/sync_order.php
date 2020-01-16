<?php

require_once(__DIR__.'/../includes/config.php');

$page_size = 250;
$page = 0;

$retry = 0;
do {
	$page++;
	$orders = $sc->get("/admin/orders.json", [
		'limit' => $page_size,
		'page' => $page,
		'order' => 'created_at asc',
		'status' => 'any',
		'created_at_min' => '2019-05-22',
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
} while(!empty($orders));