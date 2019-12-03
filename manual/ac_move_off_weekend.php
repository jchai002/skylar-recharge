<?php
require_once(__DIR__.'/../includes/config.php');

$rc = new RechargeClient();
$sc = new ShopifyClient();
/*
$charges = [];
$page = 0;
do {
	$page++;
	$res = $rc->get('/charges', ['date'=>'2019-09-03', 'limit'=>250, 'page'=>$page]);
	echo "Adding ".count($res['charges'])." charges: ";
	foreach($res['charges'] as $charge){
		foreach($charge['line_items'] as $line_item){
			if(is_ac_followup_lineitem($line_item)){
				$charges[] = $charge;
				break;
			}
		}
	}
	echo count($charges).PHP_EOL;
} while(count($res['charges']) >= 250);

echo count($charges);
die();
*/

$stmt = $db->query("SELECT rcs.recharge_id AS rc_subscription_id, rcs.next_charge_scheduled_at, rca.recharge_id AS rc_address_id FROM ac_orders aco
LEFT JOIN rc_subscriptions rcs ON rcs.id=aco.followup_subscription_id
LEFT JOIN rc_addresses rca ON rca.id=rcs.address_id
WHERE rcs.cancelled_at IS NULL
AND rcs.deleted_at IS NULL
AND rcs.next_charge_scheduled_at IN ('2019-12-03');");

$rows = $stmt->fetchAll();

echo count($rows)." rows".PHP_EOL;


foreach($rows as $row){
	$move_to_time = strtotime($row['next_charge_scheduled_at']);
//	$move_to_time = strtotime('2019-09-04');
	if($move_to_time < time()){
		$move_to_time = strtotime('tomorrow');
	}
	$move_to_time = offset_date_skip_weekend($move_to_time);
	if(offset_date_skip_weekend(strtotime(date('Y-m-').'01', $move_to_time)) == $move_to_time){
		echo "Moving to next day, SC day detected".PHP_EOL;
		$move_to_time += 25*60*60; // Add a day to offset AC from SC day
	}
	$move_to_time = offset_date_skip_weekend($move_to_time);
	echo $row['rc_subscription_id']." ".date('Y-m-d', $move_to_time).PHP_EOL;
	/*
	$res = $rc->get('/onetimes', ['address_id'=>$row['rc_address_id']]);
	foreach($res['onetimes'] as $onetime){
		insert_update_rc_subscription($db, $onetime, $rc, $sc);
	}
	*/
	$sub = get_rc_subscription($db, $row['rc_subscription_id'], $rc, $sc);
	if(!empty($sub['cancelled_at'])){
		continue;
	}
	$res = $rc->put('/onetimes/'.$row['rc_subscription_id'], [
		'next_charge_scheduled_at' => date('Y-m-d', $move_to_time),
	]);
	if(empty($res['onetime'])){
		echo "Error! ".print_r($res, true).PHP_EOL;
		sleep(10);
	} else {
		echo "Moved onetime id ".$row['rc_subscription_id']." to ".$res['onetime']['next_charge_scheduled_at'].PHP_EOL;
		insert_update_rc_subscription($db, $res['onetime'], $rc, $sc);
	}
}