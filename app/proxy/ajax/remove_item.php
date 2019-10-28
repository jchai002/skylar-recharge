<?php
global $db, $rc;

$res = $rc->get('/subscriptions/'.intval($_REQUEST['id']));
if(empty($res['subscription']) || $res['subscription']['status'] == 'ONETIME'){
	$res = $rc->delete('/onetimes/'.intval($_REQUEST['id']));
	$stmt = $db->prepare("UPDATE rc_subscriptions SET deleted_at=? WHERE recharge_id=?");
	$stmt->execute([
		date('Y-m-d H:i:s'),
		intval($_REQUEST['id']),
	]);
} else {
	$res = $this_res = $rc->post('/subscriptions/'.intval($_REQUEST['id']).'/cancel',[
		'cancellation_reason' => 'Item removed from customer account',
		'send_email' => 'false',
		'commit_update' => true,
	]);
	insert_update_rc_subscription($db, $this_res['subscription'], $rc, $sc);
}
log_event($db, 'SUBSCRIPTION', $_REQUEST['id'], 'CANCEL', 'Item removed from customer account', 'Cancelled via user account: '.json_encode($res), 'Customer');

if(!empty($res['error'])){
	echo json_encode([
		'success' => false,
		'res' => $res['error'],
	]);
} else {
	echo json_encode([
		'success' => true,
		'res' => $res,
	]);
}