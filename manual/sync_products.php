<?php

require_once(__DIR__.'/../includes/config.php');

$page_size = 250;
$url = 'products.json';
do {
	$page++;
	$products = $sc->get($url, [
		'limit' => $page_size,
	]);

	foreach($products as $product){
		echo insert_update_product($db, $product).PHP_EOL;
	}
	$url = $sc->last_response_links['next'] ?? false;
} while(!empty($url));