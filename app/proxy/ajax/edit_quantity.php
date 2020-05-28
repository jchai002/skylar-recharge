<?php

global $sc, $db, $rc;


$quantity = intval($_REQUEST['quantity']);

$res = $rc->put('subscriptions/'.intval($subscription_id), [
	'quantity' => $quantity,
]);


if(!empty($res['error'])){
	echo json_encode([
		'success' => false,
		'error' => $res['error'],
		'res' => $res,
	]);
} else {
	echo json_encode([
		'success' => true,
		'res' => $res,
		'id' => $res_id,
	]);
}