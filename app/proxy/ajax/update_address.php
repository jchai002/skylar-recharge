<?php
header('Content-Type: application/json');

global $sc, $rc, $db;

$shopify_customer = $sc->get('/admin/customers/'.$_REQUEST['c'].'.json');

$main_sub = sc_get_main_subscription($db, $rc, [
	'shopify_customer_id' => $_REQUEST['c'],
]);
$res_all = [];

$new_address = [];
if(!empty($_REQUEST['address']['country']) && $_REQUEST['address']['country'] == 'United States'){
	$_REQUEST['address']['province'] = $_REQUEST['address']['state'];
}
foreach($_REQUEST['address'] as $key => $value){
	if(!in_array($key, ['first_name', 'last_name', 'address1', 'address2', 'zip', 'city', 'province', 'country', 'phone'])){
		continue;
	}
	$new_address[$key] = $value;
}

if(empty($main_sub)){
	$res_all[] = $res = $rc->get('/customers/', [
		'shopify_customer_id' => $_REQUEST['c'],
	]);
	if(empty($res['customers'])){
		$res_all[] = $res = $rc->post('/customers/',[
			'shopify_customer_id' => $_REQUEST['c'],
			'email' => $shopify_customer['email'],
			'first_name' => $_REQUEST['first_name'],
			'last_name' => $_REQUEST['last_name'],
			'stripe_customer_token' => $token,
		]);
		$customer = $res['customer'];
	} else {
		$customer = $res['customers'][0];
	}
	$res_all[] = $res = $rc->get('/customers/'.$customer['id'].'/addresses');
	if(empty($res['addresses'])){
		$res_all[] = $res = $rc->post('/customers/'.$customer['id'].'/addresses', $new_address);
	} else {
		$res_all[] = $res = $rc->put('/addresses/'.$res['addresses'][0]['id'], $new_address);
	}
} else {
	$res_all[] = $res = $rc->put('/addresses/'.$main_sub['address_id'], $new_address);
}
if(!empty($res['error'])){
	echo json_encode([
		'success' => false,
		'error' => implode(PHP_EOL, $res['error']),
		'res' => $res_all,
	]);
} else {
	$res_all[] = $sc->put('/admin/customers/'.$_REQUEST['c'].'/addresses/'.$shopify_customer['default_address']['id'].'.json', [
		'address' => $new_address,
	]);
	echo json_encode([
		'success' => true,
		'res' => $res,
		'res_all' => $res_all,
		'new_address' => $new_address,
	]);
}