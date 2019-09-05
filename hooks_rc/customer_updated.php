<?php
require_once(__DIR__.'/../includes/config.php');

$sc = new ShopifyClient();
$rc = new RechargeClient();
// get $charge from webhook
if(!empty($_REQUEST['id'])){
	$res = $rc->get('/customers/'.$_REQUEST['id']);
} else {
	respondOK();
	$data = file_get_contents('php://input');
	if(!empty($data)){
		$res = json_decode($data, true);
	}
	log_event($db, 'webhook', $res['customer']['id'], 'customer_updated', $data);
}
$customer = $res['customer'];
var_dump($customer);

try {
	insert_update_rc_customer($db, $customer, $sc);
} catch(\Throwable $e){
	log_event($db, 'EXCEPTION', json_encode([$e->getLine(), $e->getFile(), $e->getCode(), $e->getMessage(), $e->getTraceAsString()]), 'customer_updated_insert', json_encode($customer), '', 'webhook');
}