<?php

require_once(__DIR__.'/../includes/config.php');

$order_ids = [
	2101907816535,
	2107084505175,
	2107099185239,
	2101826650199,
	2107548991575,
	2101837430871,
	2101877112919,
	2101892579415,
	2102266396759,
	2102234054743,
	2103936483415,
	2101875507287,
	2102008643671,
	2105814581335,
	2101981806679,
	2104018141271,
	2102208987223,
	2103935270999,
	2101788377175,
	2102059532375,
];

foreach($order_ids as $order_id){
	echo "Checking $order_id... ";
	// Get customer id
	$res = $rc->get('/orders', ['shopify_order_id' => $order_id]);
	if(empty($res['orders'])){
		echo "No order".PHP_EOL;
		continue;
	}
	// Check for active subscription
	echo "Checking address_id ".$res['orders'][0]['address_id'].'... ';
	$res = $rc->get('/onetimes', [
		'address_id' => $res['orders'][0]['address_id'],
	]);
	if(empty($res['onetimes'])){
		echo "No subs".PHP_EOL;
		continue;
	}
	// Print it out for now
	print_r($res);

	continue;
	// Cancel it
}

// 42900076
// 42903989