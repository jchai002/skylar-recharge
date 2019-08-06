<?php
require_once(__DIR__.'/../../includes/config.php');

$rc = new RechargeClient();
$sc = new ShopifyClient();

$interval = 5;
$page_size = 250;
$min_date = date('Y-m-d H:i:00P', time()-60*6);

// Customers
echo "Updating Customers".PHP_EOL;
$page = 0;
do {
	$page++;
	$res = $rc->get('/customers', [
		'updated_at_min' => $min_date,
		'limit' => $page_size,
		'page' => $page,
	]);
	foreach($res['customers'] as $customer){
		echo insert_update_rc_customer($db, $customer, $sc).PHP_EOL;
	}
} while(count($res) >= $page_size);

// Addresses
echo "Updating Addresses".PHP_EOL;
$page = 0;
do {
	$page++;
	$res = $rc->get('/addresses', [
		'updated_at_min' => $min_date,
		'limit' => $page_size,
		'page' => $page,
	]);
	foreach($res['addresses'] as $address){
		echo insert_update_rc_address($db, $address, $rc, $sc).PHP_EOL;
	}
} while(count($res) >= $page_size);

// Subscriptions
echo "Updating subscriptions".PHP_EOL;
$page = 0;
do {
	$page++;
	$res = $rc->get('/subscriptions', [
		'updated_at_min' => $min_date,
		'limit' => $page_size,
		'page' => $page,
	]);
	foreach($res['subscriptions'] as $subscription){
		echo insert_update_rc_subscription($db, $subscription, $rc, $sc).PHP_EOL;
	}
} while(count($res) >= $page_size);