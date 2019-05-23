<?php
require_once(__DIR__.'/../includes/config.php');
require_once(__DIR__.'/../includes/class.RechargeClient.php');

$rc = new RechargeClient();
// get $charge from webhook
if(!empty($_REQUEST['id'])){
	// cheaty since we're just using it to look up the charge
	$subscription = ['id' => $_REQUEST['id']];
} else {
	$data = file_get_contents('php://input');
	log_event($db, 'webhook', $data, 'subscription_all');
	if(!empty($data)){
		$res = json_decode($data, true);
	}
	if(empty($res['subscription'])){
		exit;
	}
	$subscription = $res['subscription'];
}

// Get next charge for this subscription
$res = $rc->get('/charges', [
	'subscription_id' => $subscription['id'],
	'status' => 'QUEUED',
]);
if(empty($res['charges'])){
	exit;
}
$charges = $res['charges'];
update_charge_discounts($db, $rc, $charges);