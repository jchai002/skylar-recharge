<?php
require_once('includes/config.php');
require_once('includes/class.ShopifyClient.php');
require_once('includes/class.RechargeClient.php');

$rc = new RechargeClient();

//$charges = $rc->get('/charges', ['subscription_id' => 21200731]);
//$charges = $rc->get('/charges', ['customer_id' => 12965232]);
$charges = $rc->get('/charges');
if(!empty($charges['charges'])){
	$charges = $charges['charges'];
}
foreach($charges as $charge){
	foreach($charge['line_item'] as $line_item){
		if($line_item['shopify_product_id'] == 738567323735 || $line_item['shopify_product_id'] == 738567520343){
			continue;
		}
		var_dump($charge);
	}
}
//var_dump($charges);
