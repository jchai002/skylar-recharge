<?php

require_once(__DIR__.'/../includes/config.php');

$page_size = 250;
$url = 'customers.json';
do {
	$customers = $sc->get($url, [
		'limit' => $page_size,
	]);

	foreach($customers as $customer){
		echo insert_update_customer($db, $customer)." ".$customer['email'].PHP_EOL;
	}
	$url = $sc->last_response_links['next'] ?? false;
} while(!empty($url));