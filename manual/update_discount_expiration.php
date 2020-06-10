<?php
require_once(__DIR__.'/../includes/config.php');

$discount_ids = $db->query("SELECT recharge_id FROM skylar.rc_discounts
WHERE code LIKE 'GS-50%'
OR code LIKE 'RT-20%';")->fetchAll(PDO::FETCH_COLUMN);

foreach($discount_ids as $discount_id){
	$res = $rc->put('discounts/'.$discount_id, ['ends_at' => '2021-01-01T00:00:00']);
	if(empty($res['discount'])){
		echo "Failed to update discount $discount_id".PHP_EOL;
		continue;
	}
	echo insert_update_rc_discount($db, $res['discount']).PHP_EOL;
}