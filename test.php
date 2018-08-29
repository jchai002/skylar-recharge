<?php
require_once('includes/config.php');
require_once('includes/class.ShopifyClient.php');
require_once('includes/class.RechargeClient.php');

$rc = new RechargeClient();

var_dump($rc->post('/webhooks/22575/test'));

//$charges = $rc->get('/charges', ['subscription_id' => 21200731]);
//$charges = $rc->get('/charges', ['customer_id' => 12965232]);
$charges = $rc->get('/charges', ['status' => 'QUEUED', 'limit' => 250]);
if(!empty($charges['charges'])){
	$charges = $charges['charges'];
}
foreach($charges as $charge){
	foreach($charge['line_items'] as $line_item){
		if(in_array($line_item['shopify_product_id'], [738567323735, 738567520343, 738394865751])){
			continue 2;
		}
		var_dump($charge);
	}
}
//var_dump($charges);
