<?php
header('Content-Type: application/json');

global $rc, $db;
$sc = new ShopifyClient();
$shopify_customer = $sc->get('/admin/customers/'.$_REQUEST['c'].'.json');

$main_sub = sc_get_main_subscription($db, $rc, [
	'shopify_customer_id' => $_REQUEST['c'],
]);
$res = [];

$new_address = [];
foreach($_REQUEST['address'] as $key => $value){
	if(!in_array($key, ['first_name', 'last_name', 'address1', 'address2', 'zip', 'city', 'province'])){
		continue;
	}
	$new_address[$key] = $value;
}

if(empty($main_sub)){
	$res = $rc->get('/customers/', [
		'shopify_customer_id' => $_REQUEST['c'],
	]);
	if(empty($res['customers'])){
		$res = $rc->post('/customers/',[
			'shopify_customer_id' => $_REQUEST['c'],
			'email' => $shopify_customer['email'],
			'billing_first_name' => $_REQUEST['first_name'],
			'billing_last_name' => $_REQUEST['last_name'],
			'first_name' => $_REQUEST['first_name'],
			'last_name' => $_REQUEST['last_name'],
			'billing_address1' => $_REQUEST['address1'],
			'billing_address2' => $_REQUEST['address2'],
			'billing_zip' => $_REQUEST['zip'],
			'billing_city' => $_REQUEST['city'],
			'billing_province' => $_REQUEST['province'],
			'billing_country' => $_REQUEST['country'],
			'stripe_customer_token' => $token,
		]);
		$customer = $res['customer'];
	} else {
		$customer = $res['customers'][0];
	}
	$res = $rc->get('/customers/'.$customer['id'].'/addresses');
	if(empty($res['addresses'])){
		$res = $rc->post('/customers/'.$customer['id'].'/addresses', $new_address);
	} else {
		$res = $rc->put('/addresses/'.$res['addresses'][0]['id'], $new_address);
	}
} else {
	$res[] = $rc->put('/addresses/'.$main_sub['address_id'], $new_address);
}
$res[] = $sc->put('/admin/customers/'.$_REQUEST['c'].'/addresses/'.$shopify_customer['default_address']['id'].'.json', [
	'address' => $new_address,
]);

echo json_encode([
	'success' => true,
	'res' => $res,
]);