<?php
require_once(__DIR__.'/../includes/config.php');

$stmt = $db->query("SELECT tracking_code FROM ac_orders aco
LEFT JOIN order_line_items oli ON aco.order_line_item_id = oli.id
LEFT JOIN fulfillments f ON oli.fulfillment_id=f.id
LEFT JOIN ep_trackers ept ON f.tracking_number=ept.tracking_code
WHERE oli.fulfillment_id IS NOT NULL
AND delivered_at IS NULL
AND ept.status='delivered';");

foreach($stmt->fetchAll(PDO::FETCH_COLUMN) as $code){
	$tracker = \EasyPost\Tracker::create([
		'tracking_code' => $code,
	]);
	echo insert_update_tracker($db, $tracker).PHP_EOL;
}