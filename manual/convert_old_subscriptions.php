<?php
require_once(__DIR__.'/../includes/config.php');
$sc = new ShopifyClient();
$rc = new RechargeClient();

$old_subs = $db->query("SELECT rcs.recharge_id AS recharge_id FROM skylar.rc_subscriptions rcs
LEFT JOIN variants v ON rcs.variant_id=v.id
LEFT JOIN products p ON v.product_id=p.id
WHERE status='ACTIVE'
AND created_at BETWEEN '2018-01-01' AND '2019-02-17'
AND next_charge_scheduled_at IS NOT NULL
AND deleted_at IS NULL")->fetchAll();

$stmt_delete_sub = $db->query('UPDATE rc_subscriptions SET deleted_at = :now WHERE recharge_id=:rc_sub_id');
foreach($old_subs as $old_sub){
	echo "Subscription ".$old_sub['recharge_id'].PHP_EOL;
	$res = $rc->get('/subscriptions/'.$old_sub['recharge_id']);
	if(empty($res['subscription'])){
		echo "Marking deleted".PHP_EOL;
		$stmt_delete_sub->execute([
			'now' => date('Y-m-d H:i:s'),
			'rc_sub_id' => $old_sub['recharge_id'],
		]);
		continue;
	}
	$subscription = $res['subscription'];
	insert_update_rc_subscription($db, $res['subscription'], $rc, $sc);
	echo "Checking charge for discount... ";
	$res = $rc->get('/charges', ['subscription_id' => $old_sub['recharge_id']]);
	if(!empty($res['charges'])){
		$charges = $res['charges'];
	}
	foreach($charges as $charge){
		if(!empty($charge['discount_codes']) && strpos(strtoupper($charge['discount_codes'][0]['code']), 'AUTOGEN') !== false){
			echo "Removing charge discount... ";
			$res = $rc->post('/charges/'.$charge['id'].'/remove_discount');
			if(!empty($res['error'])){
				echo "Error, removing address discount... ";
				$res = $rc->post('/addresses/'.$charge['address_id'].'/remove_discount');
			}
		}
	}
	echo PHP_EOL;
	if($subscription['price'] == 78){
		echo "Updating price... ";
		$res = $rc->put('/subscriptions/'.$subscription['id'], [
			'price' => 66.30,
		]);
		if(!empty($res['error'])){
			echo "Error! ";
			print_r($res);
			sleep(10);
			continue;
		}
		insert_update_rc_subscription($db, $res['subscription'], $rc, $sc);
		echo $res['subscription']['price'].PHP_EOL;
	}
}