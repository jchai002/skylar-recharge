<?php
require_once(__DIR__.'/../includes/config.php');

if(!empty($_REQUEST['id'])){
	$res = $rc->get('/addresses/'.$_REQUEST['id']);
} else {
	respondOK();
	$data = file_get_contents('php://input');
	if(!empty($data)){
		$res = json_decode($data, true);
	}
	log_event($db, 'webhook', $res['address']['id'], 'address_updated', $data);
}
if(empty($res['address'])){
	exit;
}
$address = $res['address'];
try {
	insert_update_rc_address($db, $address, $rc, $sc);
} catch(\Throwable $e){
	log_event($db, 'EXCEPTION', json_encode([$e->getLine(), $e->getFile(), $e->getCode(), $e->getMessage(), $e->getTraceAsString()]), 'address_updated_insert', json_encode($address), '', 'webhook');
}