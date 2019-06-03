<?php
// currently inactive
require_once(__DIR__.'/../includes/config.php');

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
$subscription = $res['subscription'];
var_dump($subscription);

try {
	insert_update_rc_subscription($db, $subscription, $rc, $sc);
} catch(\Throwable $e){
	log_event($db, 'EXCEPTION', json_encode($e), 'subscription_all_insert', $subscription, '', 'webhook');
}


try {
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
} catch(\Throwable $e){
	log_event($db, 'EXCEPTION', json_encode($e), 'subscription_all_rest', $subscription, '', 'webhook');
}