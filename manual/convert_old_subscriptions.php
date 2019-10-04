<?php
require_once(__DIR__.'/../includes/config.php');

// Load all active subscriptions
$subscriptions = [];
$page_size = 250;
$page = 0;
do {
	$page++;
	$res = $rc->get('/subscriptions', [
		'status' => 'ACTIVE',
		'limit' => $page_size,
		'page' => $page,
		'shopify_variant_id' => 19519443370071,
	]);
	foreach($res['subscriptions'] as $subscription){
		// Sort them by address id
		$subscriptions[] = $subscription;
	}
} while(count($res) >= $page_size);

foreach($subscriptions as $subscription){
	echo "Updating ".$subscription['id'].PHP_EOL;
	$res = $rc->put('/subscriptions/'.$subscription['id'], [
		'shopify_variant_id' => 19787922014295,
	]);
	if(empty($res['subscription'])){
		echo "Error ".print_r($res);
		die();
	}
	insert_update_rc_subscription($db, $res['subscription'], $rc, $sc);
}