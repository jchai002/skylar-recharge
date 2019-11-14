<?php

require_once(__DIR__.'/../includes/config.php');

$sc = new ShopifyClient();
$rc = new RechargeClient();

$page_size = 250;
$page = 0;
do {
	$page++;
	$res = $rc->get("/discounts", [
		'page' => $page,
		'limit' => $page_size,
	]);

	foreach($res['discounts'] as $discount){
		echo insert_update_rc_discount($db, $discount)." ".$discount['id'].PHP_EOL;
	}
} while(count($res['discounts']) >= $page_size);