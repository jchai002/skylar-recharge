<?php

require_once(__DIR__.'/../includes/config.php');

$sc = new ShopifyClient();
$rc = new RechargeClient();

$page_size = 250;
$page = 0;
do {
	$page++;
	$res = $rc->get("/subscriptions", [
		'page' => $page,
		'limit' => $page_size,
	]);

	foreach($res['subscriptions'] as $subscription){
		echo insert_update_rc_subscription($db, $subscription, $rc, $sc)." ".$subscription['id'].PHP_EOL;
	}
} while(count($res['subscriptions']) >= $page_size);