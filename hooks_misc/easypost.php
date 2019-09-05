<?php
require_once(__DIR__.'/../includes/config.php');

if(!empty($_REQUEST['code'])){
	$tracker = \EasyPost\Tracker::create([
		'tracking_code' => $_REQUEST['code'],
	]);
} else {
	respondOK();
	$data = file_get_contents('php://input');
	$event = json_decode($data, true);

	log_event($db, 'webhook', $event['id'], 'easypost_'.$event['result']['object'], $data);

	if($event['result']['object'] != 'Tracker'){
		die('Not a tracker');
	}
	$tracker = $event['result'];
}

print_r($tracker);
echo insert_update_tracker($db, $tracker);