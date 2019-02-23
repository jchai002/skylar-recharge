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
$res = $rc->get('/charges/'.$charge_id);
$charge = $res['charge'];

if($subscription['status'] == 'ONETIME'){
	$res = $rc->delete('/onetimes/'.$subscription_id);
	sc_calculate_next_charge_date($db, $rc, $subscription['address_id']);
	if(!empty($_REQUEST['unskip'])){
		sc_swap_to_monthly($db, $rc, $subscription['address_id'], strtotime($charge['scheduled_at']));
	} else {
		$main_sub = sc_get_main_subscription($db, $rc, [
			'address_id' => $subscription['address_id'],
			'status' => 'ACTIVE',
		]);
		if(!empty($main_sub)){
			$res = $rc->get('/charges', [
				'subscription_id' => $main_sub['id'],
				'date' => $charge['scheduled_at']
			]);
			if(!empty($res['charges'])){
				$res = $rc->post('/charges/'.$res['charges'][0]['id'].'/skip');
			}
		}
	}
} else {
	if(!empty($_REQUEST['unskip'])){
		$res = $rc->post('/charges/'.$charge_id.'/unskip', [
			'subscription_id' => $subscription_id
		]);
		sc_calculate_next_charge_date($db, $rc, $subscription['address_id']);
		sc_swap_to_monthly($db, $rc, $subscription['address_id'], strtotime($charge['scheduled_at']));
	} else {
		$res = $rc->post('/charges/'.$charge_id.'/skip', [
			'subscription_id' => $subscription_id
		]);
		sc_calculate_next_charge_date($db, $rc, $subscription['address_id']);
	}
}

//sc_skip_future_charge($rc, $subscription_id, $time);

echo json_encode([
	'success' => true,
	'res' => $res,
	'subscription' => $subscription,
]);