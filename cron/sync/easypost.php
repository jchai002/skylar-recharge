<?php
require_once(__DIR__.'/../../includes/config.php');

// Check in_transit shipments from before today

$stmt = $db->query("SELECT * FROM skylar.ep_trackers WHERE status = 'in_transit' AND created_at < '".date('Y-m-d')."' AND updated_at < '".date('Y-m-d')."';");

foreach($stmt->fetchAll() as $row){
	$tracker = \EasyPost\Tracker::create([
		'tracking_code' => $row['tracking_code'],
	]);
	echo $row['tracking_code'].": ".$tracker['status']." ";
	echo insert_update_tracker($db, $tracker).PHP_EOL;
}