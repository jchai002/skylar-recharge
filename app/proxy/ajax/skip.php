<?php
header('Content-Type: application/json');

global $db, $rc;

if(empty($_REQUEST['charge_id']) && !empty($_REQUEST['unskip']) && !empty($_REQUEST['subscription_id'])){
	// No charge id unskip = just move the date back
	$res = $rc->get('/subscriptions/'.intval($_REQUEST['subscription_id']));
	$res['line'] = 9;
	$subscription = $res['subscription'];
	$next_charge_date = sc_calculate_next_charge_date($db, $rc, $subscription['address_id']);
	log_event($db, 'SUBSCRIPTION', $subscription['id'], 'UNSKIP', $reason, 'Unskipped via user account: '.json_encode([$subscription,$res,$next_charge_date]), 'Customer');
	die(json_encode([
		'success' => true,
		'res' => $res,
		'next_charge_date' => $next_charge_date,
	]));

}

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
$res['line'] = 33;
$main_sub = [];
$reason = !empty($_REQUEST['reason']) ? $_REQUEST['reason'] : '';

if($subscription['status'] == 'ONETIME'){
	// Can only 'skip' onetimes if they are scent club
	// Can't actually skip onetimes, so delete it and put the scent club back, then skip
	$res = $rc->delete('/onetimes/'.$subscription_id);
	$res['line'] = 40;
	if(!empty($_REQUEST['unskip'])){
		sc_calculate_next_charge_date($db, $rc, $subscription['address_id']);
		sc_swap_to_monthly($db, $rc, $subscription['address_id'], strtotime($charge['scheduled_at']));
		/*
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
			$res['line'] = 55;
			if(!empty($res['charges'])){
				$res = $rc->post('/charges/'.$res['charges'][0]['id'].'/skip', [
					'subscription_id' => $subscription_id,
				]);
				$res['line'] = 61;
			}
		}*/
	}
	log_event($db, 'SUBSCRIPTION', $subscription['id'], 'SKIP', $reason, 'Skipped via user account: '.json_encode([$subscription,$res]), 'Customer');
} else {
	if(!empty($_REQUEST['unskip'])){
		$res = $rc->post('/charges/'.$charge_id.'/unskip', [
			'subscription_id' => $subscription_id,
		]);
		$res['line'] = 67;
		sc_swap_to_monthly($db, $rc, $subscription['address_id'], strtotime($charge['scheduled_at']));
		log_event($db, 'SUBSCRIPTION', $subscription['id'], 'UNSKIP', $reason, 'Unskipped via user account: '.json_encode([$subscription,$res]), 'Customer');
	} else {
		$res = $rc->post('/charges/'.$charge_id.'/skip', [
			'subscription_id' => $subscription_id,
		]);
		$res['line'] = 71;
		log_event($db, 'SUBSCRIPTION', $subscription['id'], 'SKIP', $reason, 'Skipped via user account: '.json_encode([$subscription,$res]), 'Customer');
		// Handle recharge bug where they double skip
		// If subscription day of month for next charge date > subscription charge day of month (normal) then it will double skip
		if(!empty($_REQUEST['theme_id'])){
			$order_day_of_month = $subscription['order_day_of_month'] ?? 1;
			if(date('d', strtotime($charge['scheduled_at'])) > $order_day_of_month){
				$next_charge_date = date('Y-m-', get_next_month(strtotime($charge['scheduled_at']))).$order_day_of_month;
				$next_charge_date = date('Y-m-d',offset_date_skip_weekend(strtotime($next_charge_date)));
				$res = [$res, $sc->post('/subscriptions/'.$subscription['id']."/set_next_charge_date", [
					'date' => $next_charge_date,
				])];
			}
		}
	}
}

echo json_encode([
	'success' => true,
	'res' => $res,
	'subscription' => $subscription,
	'main_sub' => $main_sub,
]);