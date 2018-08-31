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
		$res = json_decode($data, true);
	}
}
if(empty($res['charge'])){
	exit;
}
$charge = $res['charge'];

$res = $rc->get('/addresses/'.$charge['address_id']);
//var_dump($res);
$address = $res['address'];

$cart_attributes = [];
foreach($address['cart_attributes'] as $cart_attribute){
	if($cart_attribute['name'] == '_sample_credit'){
		continue;
	}
	$cart_attributes[] = $cart_attribute;
}
var_dump($cart_attributes);
$res = $rc->put('/addresses/'.$address['id'], [
	'cart_attributes' => $cart_attributes
]);

// Will trigger address_updated