<?php
header('Content-Type: application/json');

global $db, $rc;

if(empty($_REQUEST['subscription_id'])){
	die(json_encode([
		'success' => false,
		'errors' => [['msg'=>'Fields Missing']],
	]));
}

$reason = !empty($_REQUEST['reason']) ? $_REQUEST['reason'] : '';
$subscription_id = intval($_REQUEST['subscription_id']);

$res = $rc->delete('/onetimes/'.$subscription_id);

log_event($db, 'SUBSCRIPTION', $subscription_id, 'CANCEL', $_REQUEST['reason'], 'Cancelled via user account: '.json_encode(['autocharge_followup', $_REQUEST,$res]), 'Customer');

echo json_encode([
	'success' => true,
	'res' => $res,
]);