<?php
header('Content-Type: application/json');

global $rc, $db;

$token = $_REQUEST['token'];

$main_sub = sc_get_main_subscription($db, $rc, [
	'shopify_customer_id' => $_REQUEST['c'],
]);

$res = $rc->get('/address/'.$main_sub['address_id']);
$address = $res['address'];

$new_address = [
	'first_name' => $_REQUEST['first_name'],
];


echo json_encode([
	'success' => true,
	'res' => $res,
]);