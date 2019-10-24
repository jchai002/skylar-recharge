<?php
require_once(__DIR__.'/../../includes/config.php');

// Check in_transit shipments from before today

$date = date('Y-m-d');
$this_hour = date('H') - 1;
$this_hour = $this_hour < 0 ? 0 : $this_hour;

$max_date = date('Y-m-d', strtotime('-60 days'));

$stmt = $db->query("SELECT * FROM skylar.ep_trackers WHERE status NOT IN ('delivered', 'failure', 'return_to_sender') AND created_at BETWEEN '$max_date' AND '$date' AND updated_at < '$date $this_hour:30:00';");

echo $stmt->queryString.PHP_EOL;

foreach($stmt->fetchAll() as $row){
	$tracker = \EasyPost\Tracker::create([
		'tracking_code' => $row['tracking_code'],
	]);
	echo $row['tracking_code'].": ".$tracker['status']." ";
	echo insert_update_tracker($db, $tracker).PHP_EOL;
}