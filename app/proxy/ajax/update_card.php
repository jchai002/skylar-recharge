<?php
header('Content-Type: application/json');

global $rc, $db;

$token = $_REQUEST['token'];

$main_sub = sc_get_main_subscription($db, $rc, [
	'shopify_customer_id' => $_REQUEST['c'],
]);

$res = $rc->get('/customers/'.$main_sub['customer_id']);
$customer = $res['customer'];

\Stripe\Stripe::setApiKey($_ENV['STRIPE_API_KEY']);

$res = [];
if($customer['processor_type'] != 'stripe'){
	$res[] = $stripe_customer = \Stripe\Customer::create([
		'email' => $customer['email'],
		'source' => $token,
	]);
	$res[] = $rc->put('/customers/'.$customer['id'],[
		'stripe_customer_token' => $stripe_customer->id,
	]);
} else {
	$res[] = $stripe_customer = \Stripe\Customer::retrieve($customer['stripe_customer_token']);
	$stripe_customer->source = $token;
	$stripe_customer->save();
}

echo json_encode([
	'success' => true,
	'res' => $res,
]);