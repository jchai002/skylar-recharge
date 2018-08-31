<?php
require_once('../includes/config.php');
require_once('../includes/class.RechargeClient.php');

// Remove sample discount from address if they have one

$rc = new RechargeClient();
// get $charge from webhook
if(!empty($_REQUEST['id'])){
	$res = $rc->get('/addresses/'.$_REQUEST['id']);
} else {
	$data = file_get_contents('php://input');
	log_event($db, 'log', $data);
	if(!empty($data)){
		$res = json_decode($data, true);
	}
}
if(empty($res['address'])){
	exit;
}
$address = $res['address'];

// Get next charge for this subscription
$res = $rc->get('/charges', [
	'customer_id' => $address['customer_id'],
	'status' => 'QUEUED',
]);
if(empty($res['charges'])){
	exit;
}
$charges = $res['charges'];

update_charge_discounts($db, $rc, $charges);