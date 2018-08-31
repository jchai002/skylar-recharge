<?php
require_once('../includes/config.php');
require_once('../includes/class.RechargeClient.php');



$rc = new RechargeClient();
// get $charge from webhook
if(!empty($_REQUEST['id'])){
	$res = $rc->get('/charges/'.$_REQUEST['id']);
} else {
	$data = file_get_contents('php://input');
	if(!empty($data)){
		$res = json_decode($data, true);
	}
}
if(empty($res['charge'])){
	exit;
}
$charge = $res['charge'];

update_charge_discounts($db, $rc, [$charge]);