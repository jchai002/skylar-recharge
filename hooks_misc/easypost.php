<?php
http_response_code(200);
require_once(__DIR__.'/../includes/config.php');

if(!empty($_REQUEST['code'])){
	$tracker = \EasyPost\Tracker::create([
		'tracking_code' => $_REQUEST['code'],
	]);
} else {
	$data = file_get_contents('php://input');
	$event = json_decode($data, true);

	log_event($db, 'webhook', $event['id'], 'easypost_'.$event['result']['object'], $data);

	if($event['result']['object'] != 'Tracker'){
		die('Not a tracker');
	}
	$tracker = $event['result'];
}

echo insert_update_tracker($db, $tracker);



function insert_update_tracker(PDO $db, $tracker){
	$stmt = $db->prepare("INSERT INTO ep_trackers (easypost_id, carrier, tracking_code, status, weight, est_delivery_date, public_url, created_at, updated_at) VALUES (:easypost_id, :carrier, :tracking_code, :status, :weight, :est_delivery_date, :public_url, :created_at, :updated_at) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id), carrier=:carrier, tracking_code=:tracking_code, status=:status, weight=:weight, est_delivery_date=:est_delivery_date, public_url=:public_url, created_at=:created_at, updated_at=:updated_at");
	$stmt->execute([
		'easypost_id' => $tracker['id'],
		'carrier' => $tracker['carrier'],
		'tracking_code' => $tracker['tracking_code'],
		'status' => $tracker['status'],
		'weight' => $tracker['weight'],
		'est_delivery_date' => $tracker['est_delivery_date'],
		'public_url' => $tracker['public_url'],
		'created_at' => $tracker['created_at'],
		'updated_at' => $tracker['updated_at'],
	]);
	$tracker_id = $db->lastInsertId();
	$stmt = $db->prepare("INSERT INTO ep_tracker_details (tracker_id, message, status, source, created_at) VALUES (:tracker_id, :message, :status, :source, :created_at) ON DUPLICATE KEY UPDATE message=:message");
	foreach($tracker['tracking_details'] as $detail){
		$stmt->execute([
			'tracker_id' => $tracker_id,
			'message' => $detail['message'],
			'status' => $detail['status'],
			'source' => $detail['source'],
			'created_at' => $detail['datetime'],
		]);
	}
	return $tracker_id;
}