<?php
header('Content-Type: application/json');

global $db, $sc, $rc;

if(empty($_REQUEST['subscription_id']) || empty($_REQUEST['frequency'])){
	die(json_encode([
		'success' => false,
		'errors' => [['msg'=>'Fields Missing']],
	]));
}

$subscription = get_rc_subscription($db, intval($_REQUEST['subscription_id']), $rc, $sc);
$frequency = $_REQUEST['frequency'];

if($subscription['status'] == 'ONETIME'){
	if($frequency != 'onetime'){
		$rc->delete('/onetimes/'.$subscription['recharge_id']);
		$res = $rc->post('/subscriptions', [
			'address_id' => $subscription['recharge_address_id'],
			'next_charge_scheduled_at' => $subscription['next_charge_scheduled_at'],
			'price' => $subscription['price'],
			'quantity' => $subscription['quantity'],
			'shopify_variant_id' => $subscription['shopify_variant_id'],
			'product_title' => $subscription['product_title'],
			'variant_title' => $subscription['variant_title'],
			'order_interval_unit' => 'month',
			'order_interval_frequency' => $frequency,
			'charge_interval_frequency' => $frequency,
		]);
		if(!empty($res['subscription'])){
			insert_update_rc_subscription($db, $res['subscription'], $rc, $sc);
		}
	}
} else {
	if($frequency != 'onetime'){
		$res = $rc->put('/subscriptions/'.$subscription['recharge_id'], [
			'order_interval_frequency' => $frequency,
			'order_interval_unit' => 'month',
			'charge_interval_frequency' => $frequency,
		]);
		if(!empty($res['subscription'])){
			insert_update_rc_subscription($db, $res['subscription'], $rc, $sc);
		}
	} else {
		$rc->delete('/subscriptions/'.$subscription['recharge_id']);
		$res = $rc->post('/onetimes/address/'.$subscription['recharge_address_id'], [
			'address_id' => $subscription['recharge_address_id'],
			'next_charge_scheduled_at' => $subscription['next_charge_scheduled_at'],
			'price' => $subscription['price'],
			'quantity' => $subscription['quantity'],
			'shopify_variant_id' => $subscription['shopify_variant_id'],
			'product_title' => $subscription['product_title'],
			'variant_title' => $subscription['variant_title'],
		]);
		if(!empty($res['onetime'])){
			insert_update_rc_subscription($db, $res['onetime'], $rc, $sc);
		}
	}
}

echo json_encode([
	'success' => !empty($res['error']),
	'res' => $res,
	'old_sub' => $subscription,
]);