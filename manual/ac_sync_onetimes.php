<?php
require_once(__DIR__.'/../includes/config.php');


$stmt = $db->query("SELECT s.recharge_id FROM ac_orders aco
LEFT JOIN rc_subscriptions s ON aco.followup_subscription_id=s.id
WHERE s.deleted_at IS NULL AND s.cancelled_at IS NULL
ORDER BY next_charge_scheduled_at DESC");

$rows = $stmt->fetchAll(PDO::FETCH_COLUMN);

$stmt_delete_sub = $db->prepare("UPDATE rc_subscriptions SET deleted_at=:deleted_at WHERE recharge_id=:recharge_id");

$start_time = time();
foreach($rows AS $index=>$subscription_id){
	if($index > 0 && $index % 20 == 0){
		echo "$index of ".count($rows)." ".round($index/count($rows)*100)."%".PHP_EOL;
		echo $index/(time() - $start_time)." per second".PHP_EOL;
		echo round(($index/(time() - $start_time) * (count($rows)-$index))/60,2)."m remaining".PHP_EOL;
	}
	$res = $rc->get('/onetimes/'.$subscription_id);
	if(empty($res['onetime'])){
		$stmt_delete_sub->execute([
			'deleted_at' => date('Y-m-d H:i:s'),
			'recharge_id' => $subscription_id,
		]);
		echo "$subscription_id Deleted".PHP_EOL;
	} else {
		echo insert_update_rc_subscription($db, $res['onetime'], $rc, $sc).PHP_EOL;
	}
}