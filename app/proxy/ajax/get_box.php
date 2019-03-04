<?php
global $db;
$rc = new RechargeClient();

$month = empty($_REQUEST['month']) ? date('Y-m',strtotime('+1 month')) : date('Y-m', strtotime($_REQUEST['month']));

$ts1 = time();
$ts2 = strtotime($month.'-01');

$year1 = date('Y', $ts1);
$year2 = date('Y', $ts2);

$month1 = date('m', $ts1);
$month2 = date('m', $ts2);

$months = (($year2 - $year1) * 12) + ($month2 - $month1);


global $rc;
$res = $rc->get('/subscriptions', [
	'shopify_customer_id' => $_REQUEST['c'],
	'status' => 'ACTIVE',
]);
$subscriptions = [];
$onetimes = [];
$orders = [];
$charges = [];
$customer = [];
if(!empty($res['subscriptions'])){
	$subscriptions = $res['subscriptions'];
	$rc_customer_id = $subscriptions[0]['customer_id'];
} else {
	$res = $rc->get('/customers', [
		'shopify_customer_id' => $_REQUEST['c'],
	]);
	$customer = $res['customers'][0];
	if(!empty($customer)){
		$rc_customer_id = $customer['id'];
	}
}
if(!empty($rc_customer_id)){
	$res = $rc->get('/orders', [
		'customer_id' => $rc_customer_id,
		'status' => 'QUEUED',
	]);
	$orders = $res['orders'];
	$res = $rc->get('/charges', [
		'customer_id' => $rc_customer_id,
		'date_min' => date('Y-m-d'),
	]);
	$charges = $res['charges'];
	$res = $rc->get('/onetimes', [
		'customer_id' => $rc_customer_id,
	]);
	foreach($res['onetimes'] as $onetime){
		// Fix for api returning non-onetimes
		if($onetime['status'] == 'ONETIME'){
			$onetimes[] = $onetime;
		}
	}
}
global $db;

$upcoming_shipments = generate_subscription_schedule($db, $orders, $subscriptions, $onetimes, $charges, strtotime(date('Y-m-t',strtotime("+$months months"))));
$products_by_id = [];
$stmt = $db->prepare("SELECT * FROM products WHERE shopify_id=?");
foreach($upcoming_shipments as $upcoming_shipment){
	foreach($upcoming_shipment['items'] as $item){
		if(!array_key_exists($item['shopify_product_id'], $products_by_id)){
			$stmt->execute([$item['shopify_product_id']]);
			$products_by_id[$item['shopify_product_id']] = $stmt->fetch();
		}
	}
}

$return_box = [];
foreach($upcoming_shipments as $upcoming_shipment){
	if(date('Y-m', $upcoming_shipment['ship_date_time']) != $month){
		continue;
	}
	foreach($upcoming_shipment['items'] as $item){
		$is_scent_club = is_scent_club_any(get_product($db, $item['shopify_product_id']));
		if($is_scent_club){
			break;
		}
	}
	$return_box = $upcoming_shipment;
}


if(empty($return_box)){
	echo json_encode([
		'success' => false,
		'res' => $upcoming_shipments,
		'month' => $month,
	]);
} else {
	echo json_encode([
		'success' => true,
		'box' => $return_box,
		'res' => $upcoming_shipments,
	]);
}