<?php
require_once('includes/config.php');
require_once('includes/class.ShopifyClient.php');
require_once('includes/class.RechargeClient.php');

$rc = new RechargeClient();
$res = $rc->get('/charges/count', ['status' => 'QUEUED']);
var_dump($res);

ini_set('memory_limit','64M');

//$charges = $rc->get('/charges', ['subscription_id' => 21200731]);
//$charges = $rc->get('/charges', ['customer_id' => 12965232]);
$all_charges = [];
do{
	$res = $rc->get('/charges', ['status' => 'QUEUED', 'limit' => '250', 'page' => 2]);
	if(empty($res['charges'])){
		break;
	}
	$charges = $res['charges'];
	$all_charges = array_merge($all_charges, $charges);
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
