<?php
global $db, $sc, $rc;

$month = empty($_REQUEST['month']) ? date('Y-m',strtotime('+1 months')) : date('Y-m', strtotime($_REQUEST['month']));

$ts1 = time();
$ts2 = strtotime($month.'-01');

$year1 = date('Y', $ts1);
$year2 = date('Y', $ts2);

$month1 = date('m', $ts1);
$month2 = date('m', $ts2);

$months = (($year2 - $year1) * 12) + ($month2 - $month1);


$customer = get_customer($db, $_REQUEST['c'], $sc);
$stmt = $db->prepare("SELECT recharge_id FROM rc_customers WHERE customer_id=?");
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
}
if(empty($rc_customer_id) || empty($schedule->get())){
	echo json_encode([
		'success' => false,
		'res' => $schedule->get(),
		'month' => $month,
		'res_all' => [$schedule->subscriptions(), $schedule->charges(), $schedule->orders()],
	]);
	exit;
}

foreach($schedule->get() as $shipment_list){
	foreach($shipment_list['addresses'] as $upcoming_shipment){
		foreach($upcoming_shipment['items'] as $item){
			if(is_scent_club_any(get_product($db, $item['shopify_product_id']))){
				$return_box = $upcoming_shipment;
				$return_box['sc_product'] = get_product($db, $item['shopify_product_id']);
				break 2;
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
	]);
} else {
	echo json_encode([
		'success' => true,
		'box' => $return_box,
		'res' => $schedule->get(),
		'month' => $month,
		'res_all' => [$schedule->subscriptions(), $schedule->charges(), $schedule->orders()],
	]);
}