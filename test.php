<?php
require_once('includes/config.php');
require_once('includes/class.ShopifyClient.php');
require_once('includes/class.RechargeClient.php');

$rc = new RechargeClient();

//$charges = $rc->get('/charges', ['subscription_id' => 21200731]);
$charges = $rc->get('/charges', ['customer_id' => 12965232]);
//$charges = $rc->get('/charges');
if(!empty($charges['charges'])){
	$charges = $charges['charges'];
}
var_dump($charges);

$res = $rc->post('/charges/'.$charges[0]['id'].'/discounts/SAMPLE25/apply');