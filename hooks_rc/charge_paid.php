<?php
require_once('../includes/config.php');
require_once('../includes/class.RechargeClient.php');

// Remove sample discount from address if they have one

$rc = new RechargeClient();
// get $charge from webhook
if(!empty($_REQUEST['id'])){
	$res = $rc->get('/charges/'.$_REQUEST['id']);
} else {
	$data = file_get_contents('php://input');
	if(!empty($data)){
		$res = json_decode($data);
	}
}
if(empty($res['charge'])){
	exit;
}
$charge = $res['charge'];

$address = $rc->get('/addresses/'.$charge['address_id']);

$cart_attributes = [];
foreach($address['cart_attributes'] as $cart_attribute){
	if($cart_attribute['name'] == '_sample_discount'){
		continue;
	}
	$cart_attributes[] = $cart_attribute;
}

$res = $rc->put('/addresses/'.$address['id'], [
	'cart_attributes' => $cart_attributes
]);

var_dump($res);