<?php

global $rc;

if(empty($_REQUEST['address_id']) || empty($_REQUEST['date']) || empty($_REQUEST['variant_id'])){
	die(json_encode([
		'success' => false,
		'errors' => [['msg'=>'Fields Missing']],
	]));
}
//sc_swap_scent($rc, $_REQUEST['subscription_id'], $_REQUEST['handle'], strtotime($_REQUEST['date']));
$address_id = intval($_REQUEST['address_id']);
$variant_id = intval($_REQUEST['variant_id']);
$time = strtotime($_REQUEST['date']);


if(empty($variant_id)){
	sc_swap_to_monthly($db, $rc, $address_id, $time);
} else {
	sc_swap_to_signature($db, $rc, $address_id, $time, $variant_id);
}

echo json_encode([
	'success' => true,
]);