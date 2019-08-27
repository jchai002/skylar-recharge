<?php
require_once(__DIR__.'/../includes/config.php');

$rc = new RechargeClient();
$sc = new ShopifyClient();

$stmt = $db->query("SELECT rcs.recharge_id AS rc_subscription_id, rcs.next_charge_scheduled_at, rca.recharge_id AS rc_address_id FROM ac_orders aco
LEFT JOIN rc_subscriptions rcs ON rcs.id=aco.followup_subscription_id
LEFT JOIN rc_addresses rca ON rca.id=rcs.address_id
WHERE rcs.cancelled_at IS NULL
AND rcs.deleted_at IS NULL
AND rcs.next_charge_scheduled_at IN ('2019-09-03');");

foreach($stmt->fetchAll() as $row){
	$move_to_time = strtotime($row['next_charge_scheduled_at']);
	if($move_to_time < time()){
		$move_to_time = strtotime('tomorrow');
	}
	echo $row['rc_subscription_id'].PHP_EOL;
	$res = $rc->get('/onetimes', ['address_id'=>$row['rc_address_id']]);
	foreach($res['onetimes'] as $onetime){
		insert_update_rc_subscription($db, $onetime, $rc, $sc);
	}
	$sub = get_rc_subscription($db, $row['rc_subscription_id'], $rc, $sc);
	if(!empty($sub['cancelled_at'])){
		continue;
	}
	$res = $rc->put('/onetimes/'.$row['rc_subscription_id'], [
		'next_charge_scheduled_at' => date('Y-m-d', offset_date_skip_weekend($move_to_time)),
	]);
	if(empty($res['onetime'])){
		echo "Error! ".print_r($res, true).PHP_EOL;
		sleep(10);
	} else {
		echo "Moved onetime id ".$row['rc_subscription_id']." to ".$res['onetime']['next_charge_scheduled_at'].PHP_EOL;
		insert_update_rc_subscription($db, $res['onetime'], $rc, $sc);
	}
}