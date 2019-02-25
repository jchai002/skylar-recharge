<?php
header('Content-Type: application/json');

global $rc, $db;

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

$res[] = $rc->put('/addresses/'.$main_sub['address_id'], $new_address);

$sc = new ShopifyClient();
$customer = $sc->get('/admin/customers/'.$_REQUEST['c'].'.json');

$res[] = $sc->put('/admin/customers/'.$_REQUEST['c'].'/addresses/'.$customer['default_address']['id'].'.json', [
	'address' => $new_address,
]);

echo json_encode([
	'success' => true,
	'res' => $res,
]);