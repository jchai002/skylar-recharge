<?php
require_once(__DIR__.'/../includes/config.php');

$checkouts = [];
$page_size = 250;
$page = 0;
do {
	$page++;
	$res = $sc->get('/admin/checkouts.json', [
		'limit' => $page_size,
		'page' => $page,
		'created_at_min' => '2019-11-28 18:00:00-08:00',
		'created_at_max' => '2019-11-29 13:00:00-08:00',
	]);
	print_r($sc->last_response_headers);
	foreach($res as $checkout){
		$checkouts[] = $checkout;
	}

	echo "Adding ".count($res)." to array - total: ".count($checkouts).PHP_EOL;
} while(count($res) >= $page_size);


$outstream = fopen("checkouts.csv", 'w');
$keys = array_keys($checkouts[0]);
$keys[] = 'phone';
fputcsv($outstream, $keys);
foreach($checkouts as $checkout){
	$checkout_flat = $checkout;
	foreach($checkout as $key=>$value){
		if(!is_array($value)){
			continue;
		}
		$checkout_flat[$key] = json_encode($value);
	}
	if(!empty($checkout['billing_address']['phone'])){
		$checkout_flat['phone'] = $checkout['billing_address']['phone'];
	}
	if(!empty($checkout['shipping_address']['phone'])){
		$checkout_flat['phone'] = $checkout['shipping_address']['phone'];
	}
	fputcsv($outstream, $checkout_flat);
}