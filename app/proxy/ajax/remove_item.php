<?php

$rc = new RechargeClient();

$res = $rc->delete('/onetimes/'.intval($_REQUEST['id']));
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