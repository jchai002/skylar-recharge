<?php

global $rc;

if(empty($_REQUEST['subscription_id']) || empty($_REQUEST['date']) || empty($_REQUEST['handle'])){
	die(json_encode([
		'success' => false,
		'errors' => [['msg'=>'Fields Missing']],
	]));
}
//sc_swap_scent($rc, $_REQUEST['subscription_id'], $_REQUEST['handle'], strtotime($_REQUEST['date']));
$subscription_id = intval($_REQUEST['subscription_id']);
$handle = $_REQUEST['handle'];
$time = strtotime($_REQUEST['date']);

// Get upcoming charges, check if there is one for the current subscription date
$res = $rc->get('/charges', [
	'subscription_id' => $subscription_id,
]);
if(empty($res['charges'])){
	// TODO
}
$swap_charge = false;
foreach($res['charges'] as $charge){
	if(strtotime($charge['scheduled_at']) == $time){
		$swap_charge = $charge;
		break;
	}
}


$stmt = $db->prepare("SELECT * FROM products WHERE handle=?");
$stmt->execute([$handle]);
$product = $stmt->fetch();

$stmt = $db->prepare("SELECT * FROM products WHERE handle=?");
$stmt->execute([$handle]);
$product = $stmt->fetch();
if(is_scent_club($product)){
	// Switching to generic. Unskip


} else if (is_scent_club_month($product)){
	// Switching to specific month scent, keep skip but change onetime
	$should_be_skipped = true;
	$onetime_fill = $handle;
} else {
	// Switching to signature scent, check if skip/onetime needed and create or edit
	$should_be_skipped = true;
	$onetime_fill = $handle;
}
// Check if subscription is monthly or not
$is_monthly = true; // TODO

if($should_be_skipped && empty($swap_charge)){
	// Skip all the way to this charge, then unskip everything but it
} else if($should_be_skipped && $swap_charge['status'] == 'QUEUED'){
	// Skip charge
} else if(!$should_be_skipped && !empty($swap_charge) && $swap_charge['status'] == 'SKIPPED'){
	// Unskip charge
}
