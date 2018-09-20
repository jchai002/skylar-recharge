<?php
require_once('includes/config.php');
require_once('includes/class.ShopifyClient.php');
require_once('includes/class.RechargeClient.php');

$rc = new RechargeClient();
/*
$res = $rc->get('/subscriptions', ['customer_id' => 15240553]);
foreach($res['subscriptions'] as $subscription){
	if($subscription['shopify_product_id'] == '8215317379'){
		$res = $rc->delete('/subscriptions/'.$subscription['id']);
	}
}
*/
//var_dump($res);
//$res = $rc->get('/addresses/16393009');
//var_dump($res);
//$res = $rc->get('/customers/14587855');
$res = $rc->get('/subscriptions/', ['address_id' => 16192600]);
//$res = $rc->get('/charges/', ['customer_id' => 14954506]);
//$res = $rc->delete('/subscriptions/22190467');
var_dump($res);





die();

$res = $rc->get('/subscriptions/', ['address_id' => 16130191]);
$subscriptions = $res['subscriptions'];
foreach($subscriptions as $subscription){
	$res = $rc->get('/charges/', ['subscription_id' => $subscription['id'], 'status' => 'QUEUED']);
	if(!empty($res['charges'])){
		$charge = $res['charges'][0];
		var_dump($charge);
		$discount_factors = calculate_discount_factors($rc, $charge);
		var_dump($discount_factors);
		$discount_amount = calculate_discount_amount($charge, $discount_factors);
		var_dump($discount_amount);

		$code = get_charge_discount_code($db, $rc, $discount_amount);
		var_dump($code);
	}
//	var_dump($res);
}

die();


//$res = $rc->get('/addresses/16050958');
$res = $rc->get('/subscriptions/', ['address_id' => 16050958]);

var_dump($res);


die();

$res = $rc->put('/addresses/16048888', [
	'cart_attributes' => [['name' => '_sample_credit', 'value' => 20]],
]);
var_dump($res);

die();

$res = $rc->get('/addresses/15901834');
$address = $res['address'];

$ch = curl_init('https://ec2staging.skylar.com/hooks_rc/address_updated.php');
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
