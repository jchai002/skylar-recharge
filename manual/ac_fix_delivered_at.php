<?php
require_once(__DIR__.'/../includes/config.php');

// Pull ac fulfillments
$stmt = $db->query("
SELECT o.created_at, f.id, f.tracking_number AS tracking_code, f.delivered_at,
    DATE(DATE_ADD(f.delivered_at, INTERVAL 14 DAY)) AS delivered_date_plus_14
FROM skylar.orders o
LEFT JOIN order_line_items oli ON o.id=oli.order_id
LEFT JOIN fulfillments f ON f.id=oli.fulfillment_id
LEFT JOIN ac_orders aco ON oli.id=aco.order_line_item_id
LEFT JOIN rc_subscriptions s ON aco.followup_subscription_id=s.id
WHERE o.created_at >= '2019-08-28' AND o.created_at < '2019-09-21'
AND oli.properties LIKE '%_ac_trigger%'
AND o.cancelled_at IS NULL
AND f.delivered_at IS NOT NULL # 3 tracking number issues
AND DATEDIFF(f.delivered_at, o.created_at) > 5
AND DATE(DATE_ADD(f.delivered_at, INTERVAL 14 DAY)) > '2019-09-24'
;");

$stmt_update_fullfilment_processed = $db->prepare("UPDATE fulfillments f SET delivered_at = ?, delivery_processed_at = null WHERE f.id=?");
foreach($stmt->fetchAll() as $row){
	// Get delivery dates from ep tracker
	$tracker = \EasyPost\Tracker::create([
		'tracking_code' => $row['tracking_code'],
	]);
	echo $row['tracking_code'].": ".$tracker['status']." ";
	if(strtotime($row['delivered_at']) == strtotime($tracker))

	// Update delivery date, set processed to null
	echo insert_update_tracker($db, $tracker).PHP_EOL;
	foreach($tracker['tracking_details'] as $detail){
		if($detail['status'] == 'delivered'){
			if(strtotime($row['delivered_at']) == strtotime($detail['datetime'])){
				echo "Skipping, already correct".PHP_EOL;
				break;
			}
			echo "Updating delivery date and unmarking processed".PHP_EOL;
			$stmt_update_fullfilment_processed->execute([$detail['datetime'], $row['id']]);
			$error = $stmt_update_fullfilment_processed->errorInfo();
			if($error[0] != 0){
				die();
			}
			break;
		}
	}
}