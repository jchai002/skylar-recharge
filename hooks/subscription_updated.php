<?php
require_once('../includes/config.php');
require_once('../includes/class.ShopifyClient.php');
require_once('../includes/class.RechargeClient.php');

// When a subscription is created or updated, calculate the pricing rules

// TODO: Get webhook data

$rc = new RechargeClient();
$subscriptions = $rc->get('/subscriptions', [
	'shopify_customer_id' => $_REQUEST['customer_id'],
]);
if(empty($subscriptions['subscriptions'])){
	die(json_encode($subscriptions));
}
$subscriptions = $subscriptions['subscriptions'];


foreach($subscriptions as $subscription){

}








/* All configurations:
Sub / No Sub
1 Bottle, 2 Bottles, 3 Bottles, 4+ Bottles
Sample / No Sample