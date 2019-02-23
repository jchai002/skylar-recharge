<?php
header('Content-Type: application/json');

global $db, $rc;

if(empty($_REQUEST['subscription_id']) || empty($_REQUEST['charge_id'])){
	die(json_encode([
		'success' => false,
		'errors' => [['msg'=>'Fields Missing']],
	]));
}

// Might need to check if its a onetime

$subscription_id = intval($_REQUEST['subscription_id']);
$charge_id = intval($_REQUEST['charge_id']);

if(!empty($_REQUEST['unskip'])){
	$res = $rc->post('/charges/'.$charge_id.'/unskip', [
		'subscription_id' => $subscription_id
	]);
} else {
	$res = $rc->post('/charges/'.$charge_id.'/skip', [
		'subscription_id' => $subscription_id
	]);
}

//sc_skip_future_charge($rc, $subscription_id, $time);

echo json_encode([
	'success' => true,
	'res' => $res,
]);