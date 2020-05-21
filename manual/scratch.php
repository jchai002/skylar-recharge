<?php
require_once(__DIR__.'/../includes/config.php');

$sub_ids = $db->query("SELECT rcs.recharge_id FROM skylar.rc_subscriptions rcs
LEFT JOIN rc_addresses rca ON rcs.address_id=rca.id
LEFT JOIN rc_customers rcc ON rca.rc_customer_id=rcc.id
LEFT JOIN variants v ON v.id=rcs.variant_id
LEFT JOIN products p ON p.id=v.product_id
WHERE rcs.created_at >= '2020-05-21 00:00:00'
AND rcs.cancelled_at IS NULL
AND rcs.deleted_at IS NULL
AND rcs.status = 'ONETIME'
AND rcs.variant_id=12888
ORDER BY rcs.created_at ASC
;")->fetchAll(PDO::FETCH_COLUMN);

foreach($sub_ids as $id){
	$res = $rc->get('onetimes/'.$id);
	echo $id;
	print_r($res['onetime'] ?? []);
	if(empty($res['onetime'])){
		$stmt = $db->prepare("UPDATE rc_subscriptions SET deleted_at=? WHERE recharge_id=?");
		$stmt->execute([
			date('Y-m-d H:i:s'),
			$id,
		]);
	} else {
		insert_update_rc_subscription($db, $res['onetime'], $rc, $sc);
	}
}