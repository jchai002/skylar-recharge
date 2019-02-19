<?php

require_once(__DIR__.'/../includes/config.php');
require_once(__DIR__.'/../includes/class.ShopifyClient.php');

$sc = new ShopifyClient();

$page_size = 250;
$page = 0;
do {
	$page++;
	$products = $sc->get("/admin/products.json", [
		'page' => $page,
		'limit' => $page_size,
	]);

	foreach($products as $product){
		echo insert_update_product($db, $product);
	}
} while(count($products) >= $page_size);