<?php

require_once('../includes/config.php');
require_once('../includes/class.ShopifyClient.php');
require_once('../includes/class.RechargeClient.php');

$rc = new RechargeClient();
$sc = new ShopifyPrivateClient();

$page = 1;
do {
	echo "Starting page $page" . PHP_EOL;
	$sub_res = $rc->get('/addresses', ['limit' => 250, 'page' => $page, 'updated_at_min'=>'2018-09-05T00:00:00']);

	foreach($sub_res['addresses'] as $address){
		$attributes = [];
		foreach($address['cart_attributes'] as $cart_attribute){
			$attributes[$cart_attribute['name']] = $cart_attribute['value'];
		}
		if(!empty($attributes['_sample_credit'])){
			continue;
		}
		$address['cart_attributes'][] = ['name' => '_sample_credit', 'value' => '20'];
		$res = $rc->put('/addresses/'.$address['id'], ['cart_attributes'=> $address['cart_attributes']]);
		echo "Added to ".$address['id'].PHP_EOL;
		sleep(2);
	}

	$page++;
	sleep(5);
} while(count($sub_res['addresses']) >= 250);