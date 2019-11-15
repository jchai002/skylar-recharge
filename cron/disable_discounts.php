<?php
require_once(__DIR__.'/../includes/config.php');

// Load discount disable schedule
$now = date('Y-m-d H:i:s');
$discount_schedule = $db->query("SELECT ds.type, ds.id as discount_schedule_id, d.recharge_id as discount_id, d.code FROM rc_discount_schedule ds
LEFT JOIN rc_discounts d ON d.id=ds.rc_discount_id
WHERE triggered_at IS NULL
AND trigger_after <= '$now'")->fetchAll();

echo $db->query("SELECT ds.type, ds.id as discount_schedule_id, d.recharge_id as discount_id, d.code FROM rc_discount_schedule ds
LEFT JOIN rc_discounts d ON d.id=ds.rc_discount_id
WHERE triggered_at IS NULL
AND trigger_after <= '$now'")->queryString;

$stmt_update_schedule = $db->prepare("UPDATE rc_discount_schedule SET triggered_at = '$now' WHERE id=?");
foreach($discount_schedule as $row){
	if($row['type'] == 'disable'){
		$res = $rc->put('/discounts/'.$row['discount_id'], [
			'status' => 'disabled',
		]);
		if(!empty($res['discount'])){
			echo "updated .".insert_update_rc_discount($db, $res['discount'])." ".$row['code'].PHP_EOL;
			$stmt_update_schedule->execute([$row['discount_schedule_id']]);
		} else {
			echo "error!".PHP_EOL;
			print_r($res);
		}
	}
}