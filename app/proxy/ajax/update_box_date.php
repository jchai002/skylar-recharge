<?php
header('Content-Type: application/json');

global $db, $rc;

$res_all = [];
$res_all[] = $res = $rc->get('/charges/'.$_REQUEST['charge_id']);
$day_of_month = date('d', strtotime($_REQUEST['date']));
if(!empty($res['charge'])){
	$charge = $res['charge'];

	$res_all[] = $rc->post('/charges/'.$_REQUEST['charge_id'].'/change_next_charge_date', [
		'next_charge_date' => date('Y-m-d', strtotime($_REQUEST['date'])),
	]);

	if(!empty($_REQUEST['update_type']) && $_REQUEST['update_type'] == 'all'){
		$res_all[] = $res = $rc->get('/onetimes/', [
			'status' => 'ONETIME',
			'address_id' => $charge['address_id'],
		]);
		if(!empty($res['onetimes'])){
			foreach($res['onetimes'] as $onetime){
				if($onetime['status'] != 'ONETIME'){
					continue;
				}
				$onetime_time = strtotime($onetime['next_charge_scheduled_at']);
				$this_day_of_month = date('t', $onetime_time) < $day_of_month ? date('t', $onetime_time) : $day_of_month;
				$res_all[] = $rc->put('/onetimes/'.$onetime['id'],[
					'next_charge_date' => date('Y-m', $onetime_time).'-'.$this_day_of_month
				]);
			}
		}
		$res_all[] = $res = $rc->get('/subscriptions/', [
			'status' => 'ACTIVE',
			'address_id' => $charge['address_id'],
		]);
		if(!empty($res['subscriptions'])){
			foreach($res['subscriptions'] as $subscription){
				$res_all[] = $rc->put('/subscriptions/'.$subscription['id'],[
					'order_day_of_month' => $day_of_month,
					'commit_update' => true,
				]);
			}
		}

		$res_all[] = $rc->
	}
	//sc_calculate_next_charge_date($db, $rc, $charge['address_id']);
}


echo json_encode([
	'success' => true,
	'res' => $res_all,
]);