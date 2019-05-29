<?php

require_once(__DIR__.'/../includes/config.php');
require_once(__DIR__.'/../includes/class.ShopifyClient.php');

$sc = new ShopifyClient();

$page_size = 250;
$page = 0;
do {
	$page++;
	$customers = $sc->get("/admin/customers.json", [
		'page' => $page,
		'limit' => $page_size,
	]);

	foreach($customers as $customer){
		echo insert_update_customer($db, $customer)." ".$customer['email'].PHP_EOL;
	}
} while(count($customers) >= $page_size);