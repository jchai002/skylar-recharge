<?php
require_once('../includes/config.php');

$rc = new RechargeClient();
// get $charge from webhook
if(!empty($_REQUEST['id'])){
	$res = $rc->get('/checkouts/'.$_REQUEST['id']);
} else {
	$data = file_get_contents('php://input');
	if(!empty($data)){
		$res = json_decode($data, true);
	}
}
$checkout = $res['checkout'];
print_r($res);
die();

// Check if customer already has a subscription
$main_sub = sc_get_main_subscription($db, $rc, [
	'status' => 'ACTIVE',
	'shopify_customer_id' => ''
]);

foreach($checkout['line_items'] as $line_item){

}