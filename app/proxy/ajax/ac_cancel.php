<?php
header('Content-Type: application/json');

global $db, $rc;

if(empty($_REQUEST['subscription_id'])){
	die(json_encode([
		'success' => false,
		'errors' => [['msg'=>'Fields Missing']],
	]));
}

$subscription_id = intval($_REQUEST['subscription_id']);

$res = $rc->delete('/onetimes/'.$subscription_id);

echo json_encode([
	'success' => true,
	'res' => $res,
]);