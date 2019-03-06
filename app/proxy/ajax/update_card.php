<?php
header('Content-Type: application/json');

global $rc, $db;
$sc = new ShopifyClient();

$token = $_REQUEST['token'];

$res = $rc->get('/customers/', [
	'shopify_customer_id' => $_REQUEST['c'],
]);

\Stripe\Stripe::setApiKey($_ENV['STRIPE_API_KEY']);

if(empty($res['customers'])){
	$res = $stripe_customer = \Stripe\Customer::retrieve($customer['stripe_customer_token']);
	$stripe_customer->source = $token;
	$stripe_customer->save();

	$shopify_customer = $sc->get('/admin/customers/'.$_REQUEST['c'].'.json');
	$res = $rc->post('/customers/',[
		'shopify_customer_id' => $_REQUEST['c'],
		'email' => $shopify_customer['email'],
		'first_name' => $shopify_customer['first_name'],
		'last_name' => $shopify_customer['last_name'],
		'billing_first_name' => $_REQUEST['billing_first_name'],
		'billing_last_name' => $_REQUEST['billing_last_name'],
		'billing_address1' => $_REQUEST['billing_address1'],
		'billing_address2' => $_REQUEST['billing_address2'],
		'billing_zip' => $_REQUEST['billing_zip'],
		'billing_phone' => $_REQUEST['billing_zip'],
		'billing_city' => $_REQUEST['billing_city'],
		'billing_province' => $_REQUEST['billing_province'],
		'billing_country' => $_REQUEST['billing_country'],
		'stripe_customer_token' => $token,
	]);
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
} else {
	$customer = $res['customers'][0];
	$res = [];
	if($customer['processor_type'] != 'stripe'){
		$res[] = $stripe_customer = \Stripe\Customer::create([
			'email' => $customer['email'],
			'source' => $token,
		]);
		$res[] = $rc->put('/customers/'.$customer['id'],[
			'stripe_customer_token' => $stripe_customer->id,
			'billing_first_name' => $_REQUEST['billing_first_name'],
			'billing_last_name' => $_REQUEST['billing_last_name'],
			'billing_address1' => $_REQUEST['billing_address1'],
			'billing_address2' => $_REQUEST['billing_address2'],
			'billing_zip' => $_REQUEST['billing_zip'],
			'billing_phone' => $_REQUEST['billing_zip'],
			'billing_city' => $_REQUEST['billing_city'],
			'billing_province' => $_REQUEST['billing_province'],
			'billing_country' => $_REQUEST['billing_country'],
		]);
	} else {
		$res[] = $stripe_customer = \Stripe\Customer::retrieve($customer['stripe_customer_token']);
		$stripe_customer->source = $token;
		$stripe_customer->save();
	}
}


echo json_encode([
	'success' => true,
	'res' => $res,
]);