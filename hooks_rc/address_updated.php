<?php
require_once(__DIR__.'/../includes/config.php');


// Remove sample discount from address if they have one

$rc = new RechargeClient();
if(!empty($_REQUEST['id'])){
	$res = $rc->get('/addresses/'.$_REQUEST['id']);
} else {
	$data = file_get_contents('php://input');
	if(!empty($data)){
		$res = json_decode($data, true);
	}
	log_event($db, 'webhook', $res['address']['id'], 'address_updated', $data);
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