<?php
require_once(__DIR__.'/../includes/config.php');

if(!empty($_REQUEST['id'])){
	$res = $rc->get('/discounts/'.$_REQUEST['id']);
} else {
	respondOK();
	$data = file_get_contents('php://input');
	if(!empty($data)){
		$res = json_decode($data, true);
	}
	log_event($db, 'webhook', $res['discount']['id'], 'rc_discount_updated', $data);
}
if(empty($res['discount'])){
	exit;
}
$discount = $res['discount'];
try {
	insert_update_rc_discount($db, $discount);
} catch(\Throwable $e){
	log_event($db, 'EXCEPTION', json_encode([$e->getLine(), $e->getFile(), $e->getCode(), $e->getMessage(), $e->getTraceAsString()]), 'rc_discount_updated_insert', json_encode($discount), '', 'webhook');
}