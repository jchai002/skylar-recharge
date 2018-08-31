<?php
require_once('includes/config.php');
require_once('includes/class.ShopifyClient.php');
require_once('includes/class.RechargeClient.php');

$rc = new RechargeClient();


$res = $rc->get('/addresses/15901834');
$address = $res['address'];

$ch = curl_init('https://ec2staging.skylar.com/hooks_rc/address_updated.php?id=15901834');
curl_setopt_array($ch, [
	CURLOPT_RETURNTRANSFER =>  true,
	CURLOPT_POST => true,
	CURLOPT_POSTFIELDS => json_encode($res),
]);
echo curl_exec($ch);



die();


$res = $rc->get('/charges', [
	'subscription_id' => 21200731,
	'status' => 'QUEUED',
]);
var_dump($res);
if(empty($res['charges'])){
	exit;
}

die();


$res = $rc->get('/discounts', [
	'discount_type' => 'fixed_amount',
	'status' => 'enabled',
	'limit' => 250,
]);
var_dump($res);

die();


$res = $rc->get('/charges/count', ['status' => 'QUEUED']);
var_dump($res);

//$charges = $rc->get('/charges', ['subscription_id' => 21200731]);
//$charges = $rc->get('/charges', ['customer_id' => 12965232]);
$all_charges = [];
$page = 1;
do{
	$res = $rc->get('/charges', ['status' => 'QUEUED', 'limit' => '250', 'page' => $page]);
	if(empty($res['charges'])){
		break;
	}
	$charges = $res['charges'];
	$all_charges = array_merge($all_charges, $charges);
	$page++;
} while(count($charges) >= 250);

foreach($all_charges as $charge){
	foreach($charge['line_items'] as $line_item){
		if(in_array($line_item['shopify_product_id'], [738567323735, 738567520343, 738394865751])){
			continue 2;
		}
		var_dump($charge);
	}
}
//var_dump($charges);
