<?php
header('Content-Type: application/json');

global $db, $rc;

if(empty($_REQUEST['subscription_id']) || empty($_REQUEST['date'])){
	die(json_encode([
		'success' => false,
		'errors' => [['msg'=>'Fields Missing']],
	]));
}
//sc_swap_scent($rc, $_REQUEST['subscription_id'], $_REQUEST['handle'], strtotime($_REQUEST['date']));
$subscription_id = intval($_REQUEST['subscription_id']);
$time = strtotime($_REQUEST['date']);

sc_skip_future_charge($rc, $subscription_id, $time);

echo json_encode([
	'success' => true,
]);