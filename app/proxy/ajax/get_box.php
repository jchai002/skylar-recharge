<?php
global $db, $sc, $rc;

if(!empty($_REQUEST['month'])){
	$month = date('Y-m', strtotime($_REQUEST['month']));

	// Calc number of months from then to now
	$ts1 = time();
	$ts2 = strtotime($month.'-01');

	$year1 = date('Y', $ts1);
	$year2 = date('Y', $ts2);

	$month1 = date('m', $ts1);
	$month2 = date('m', $ts2);
	$months = (($year2 - $year1) * 12) + ($month2 - $month1);
	$months = $months > 6 ? $months : 6;
} else {
	$months = 6;
}


$customer = get_customer($db, $_REQUEST['c'], $sc);
$stmt = $db->prepare("SELECT recharge_id FROM rc_customers WHERE id=?");
$stmt->execute([$customer['id']]);
if($stmt->rowCount() > 1){
	$rc_customer_id = $stmt->fetchColumn();
} else {
	$res = $rc->get('/customers', [
		'shopify_customer_id' => intval($_REQUEST['c']),
	]);
	if(!empty($res['customers'])){
		$rc_customer_id = $res['customers'][0]['id'];
	}
}

if(!empty($rc_customer_id)){
	$schedule = new SubscriptionSchedule($db, $rc, $rc_customer_id, strtotime(date('Y-m-t',strtotime("+$months months"))));
	if(empty($rc_customer_id) || empty($schedule->get())){
		header('Content-Type: application/json');
		echo json_encode([
			'success' => false,
			'res' => $schedule->get(),
			'month' => $month,
			'res_all' => [$schedule->subscriptions(), $schedule->charges(), $schedule->orders()],
			'rc_customer_id' => $rc_customer_id,
		]);
		exit;
	}
	foreach($schedule->get() as $shipment_list){
		foreach($shipment_list['addresses'] as $upcoming_shipment){
			foreach($upcoming_shipment['items'] as $item){
				if(empty($_REQUEST['month']) && !empty($item['skipped'])){
					continue;
				}
				if(is_scent_club_any(get_product($db, $item['shopify_product_id']))){
					$return_box = $upcoming_shipment;
					$return_box['sc_product'] = get_product($db, $item['shopify_product_id']);
					$month = date('Y-m', $return_box['ship_date_time']);
					break 3;
				}
			}
		}
	}
}
if(empty($return_box)){
	echo json_encode([
		'success' => false,
		'res' => $schedule->get(),
		'month' => $month,
		'res_all' => [$schedule->subscriptions(), $schedule->charges(), $schedule->orders()],
		'rc_customer_id' => $rc_customer_id,
	]);
} else {
	echo json_encode([
		'success' => true,
		'box' => $return_box,
		'res' => $schedule->get(),
		'month' => $month,
		'res_all' => [$schedule->subscriptions(), $schedule->charges(), $schedule->orders()],
		'rc_customer_id' => $rc_customer_id,
	]);
}