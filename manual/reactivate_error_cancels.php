<?php
require_once(__DIR__ . '/../includes/config.php');


$date = '2020-02-01';

$rows = $db->query("SELECT *, rcs.recharge_id AS rc_subscription_id, rca.recharge_id AS rc_address_id FROM rc_subscriptions rcs
LEFT JOIN rc_addresses rca ON rcs.address_id=rca.id
WHERE cancelled_at >= '$date'
AND cancellation_reason = 'Auto-cancelled - Invalid Payment Method Not Fixed'
AND variant_id = 6650;")->fetchAll();

foreach($rows as $row){
	echo $row['rc_address_id']." ";
	// Get error charges, check the error_type for valid/invalid
	$res = $rc->get('/charges', ['status'=>'error', 'address_id'=>$row['rc_address_id'], 'date_min'=>'2020-01-15',]);
	// If no error charges with type "CLOSED_MAX_RETRIES_REACHED", reactivate
	foreach($res['charges'] as $error_charge){
		if($error_charge['error_type'] == 'CLOSED_MAX_RETRIES_REACHED'){
			echo "Skipping, ".$error_charge['id']." type ".$error_charge['error_type'].PHP_EOL;
			continue 2;
		}
	}
	echo "Activating ".$row['rc_subscription_id']."... ";
	$res = $rc->post('/subscriptions/'.$row['rc_subscription_id']."/activate");
	if(empty($res['subscription'])){
		print_r($res);
		die("error activating!");
	}
	insert_update_rc_subscription($db, $res['subscription'], $rc, $sc);
	echo sc_calculate_next_charge_date($db, $rc, $row['rc_address_id'], $res['subscription'], 1);
	echo " Done".PHP_EOL;
}