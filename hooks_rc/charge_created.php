<?php
require_once(__DIR__.'/../includes/config.php');

// get $charge from webhook
if(!empty($_REQUEST['id'])){
	$res = $rc->get('/charges/'.$_REQUEST['id']);
} else {
	respondOK();
	$data = file_get_contents('php://input');
	if(!empty($data)){
		$res = json_decode($data, true);
	}
	log_event($db, 'webhook', $res['charge']['id'], 'charge_paid', $data);
}
var_dump($res);
if(empty($res['charge'])){
	echo "no charge";
	exit;
}
$charge = $res['charge'];

$stmt = $db->prepare("UPDATE orders SET charge_processed_at = :now WHERE shopify_id = :order_id");
$stmt->execute([
	'now' => date('Y-m-d H:i:s'),
	'order_id' => $charge['shopify_order_id']
]);