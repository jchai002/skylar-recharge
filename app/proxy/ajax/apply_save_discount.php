<?php
global $db, $sc, $rc;

$db_subscription = get_rc_subscription($db, $_REQUEST['subscription_id'], $rc, $sc);
$db_address = get_rc_address($db, $db_subscription['recharge_address_id'], $rc, $sc);

$discount_code = "ST-10-".$db_address['recharge_customer_id'];
// Check if discount already exists
$stmt = $db->query("SELECT * FROM rc_discounts WHERE code='$discount_code'");
if($stmt->rowCount() == 0){
	// Create discount
	$res_all[] = $res = $rc->post('/discounts', [
		'code' => $discount_code,
		'discount_type' => 'fixed_amount',
		'value' => '10',
		'duration' => 'single_use',
		'usage_limit' => '1',
	]);
	if(!empty($res['discount'])){
		insert_update_rc_discount($db, $res['discount']);
	}
}

// apply it to the next charge on the subscription id
$res_all[] = $res = $rc->get('/charges/', ['subscription_id' => intval($_REQUEST['subscription_id'])]);
if(!empty($res['charges'])){
	$charge_id = $res['charges'][0]['id'];
}
$res_all[] = $res = $rc->post('/addresses/'.$db_address['recharge_id'].'/remove_discount');
$res_all[] = $res = $rc->post('/charges/'.$charge_id.'/apply_discount', [
	'discount_code' => $discount_code,
]);

echo json_encode([
	'discount_code' => $discount_code,
	'success' => true,
	'res' => $res,
]);