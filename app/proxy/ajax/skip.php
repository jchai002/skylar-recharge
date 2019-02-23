<?php
header('Content-Type: application/json');

global $db, $rc;

if(empty($_REQUEST['subscription_id']) || empty($_REQUEST['charge_id'])){
	die(json_encode([
		'success' => false,
		'errors' => [['msg'=>'Fields Missing']],
	]));
}

$subscription_id = intval($_REQUEST['subscription_id']);
$charge_id = intval($_REQUEST['charge_id']);

$res = $rc->get('/subscriptions/'.$subscription_id);
$subscription = $res['subscription'];

// Might need to check if its a onetime
if($subscription['status'] == 'ONETIME'){
	$res = $rc->delete('/onetimes/'.$subscription_id);
} else {
	if(!empty($_REQUEST['unskip'])){
		$res = $rc->post('/charges/'.$charge_id.'/unskip', [
			'subscription_id' => $subscription_id
		]);
	} else {
		$res = $rc->post('/charges/'.$charge_id.'/skip', [
			'subscription_id' => $subscription_id
		]);
	}
}
sc_calculate_next_charge_date($db, $rc, $subscription['address_id']);

//sc_skip_future_charge($rc, $subscription_id, $time);

echo json_encode([
	'success' => true,
	'res' => $res,
]);