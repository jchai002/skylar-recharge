<?php
header('Content-Type: application/json');

global $db, $sc, $rc;

if(empty($_REQUEST['subscription_id']) || empty($_REQUEST['date'])){
	die(json_encode([
		'success' => false,
		'errors' => [['msg'=>'Fields Missing']],
	]));
}

$subscription = get_rc_subscription($db, intval($_REQUEST['subscription_id']), $rc, $sc);


if($subscription['status'] == 'ONETIME'){
	$res = $rc->put('/onetimes/'.$subscription['recharge_id'], [
		'next_charge_scheduled_at' => $_REQUEST['date']
	]);
	if(!empty($res['onetime'])){
		insert_update_rc_subscription($db, $res['onetime'], $rc, $sc);
	}
} else {
	$res = $rc->post('/subscriptions/'.$subscription['recharge_id'].'/set_next_charge_date', [
		'date' => $_REQUEST['date']
	]);
	if(!empty($res['subscription'])){
		insert_update_rc_subscription($db, $res['subscription'], $rc, $sc);
	}
}

echo json_encode([
	'success' => !empty($res['error']),
	'res' => $res,
	'old_sub' => $subscription,
]);