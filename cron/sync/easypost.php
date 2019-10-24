<?php
require_once(__DIR__.'/../../includes/config.php');

// Check in_transit shipments from before today

$date = date('Y-m-d');

$stmt = $db->query("SELECT * FROM skylar.ep_trackers WHERE status NOT IN ('delivered', 'failure', 'return_to_sender') AND created_at < '$date' AND updated_at < '$date';");

foreach($stmt->fetchAll() as $row){
	$tracker = \EasyPost\Tracker::create([
		'tracking_code' => $row['tracking_code'],
	]);
	echo $row['tracking_code'].": ".$tracker['status']." ";
	echo insert_update_tracker($db, $tracker).PHP_EOL;
}