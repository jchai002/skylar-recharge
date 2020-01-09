<?php
header('Content-Type: application/json');

global $rc, $db;
$sc = new ShopifyClient();

$token = $_REQUEST['token'];
$res_all = [];

$res_all[] = $res = $rc->get('/customers/', [
	'shopify_customer_id' => $_REQUEST['c'],
]);

\Stripe\Stripe::setApiKey($_ENV['STRIPE_API_KEY']);

try {
	if(empty($res['customers'])){
		$error = "Recharge customer doesn't exist - no way to access stripe account";
	} else {
		$customer = $res['customers'][0];
		if($customer['processor_type'] != 'stripe'){
			$res_all[] = $res = $stripe_customer = \Stripe\Customer::create([
				'email' => $customer['email'],
				'source' => $token,
			]);
			$res_all[] = $res = $rc->put('/customers/' . $customer['id'], [
				'stripe_customer_token' => $stripe_customer->id,
				'first_name' => $_REQUEST['billing_first_name'],
				'last_name' => $_REQUEST['billing_last_name'],
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
			$res_all[] = $res = $stripe_customer = \Stripe\Customer::retrieve($customer['stripe_customer_token']);
			$stripe_customer->source = $token;
			$stripe_customer->invoice_settings->default_payment_method = null;
			$stripe_customer->save();
			$res_all[] = $res = $rc->put('/customers/' . $customer['id'], [
				'first_name' => $_REQUEST['billing_first_name'],
				'last_name' => $_REQUEST['billing_last_name'],
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
		}
	}
} catch (\Stripe\Error\Card $e){
	$error = $e;
}
if(!empty($error)){
	echo json_encode([
		'success' => false,
		'res' => $res_all,
		'error' => $error,
	]);
} else {
	echo json_encode([
		'success' => true,
		'res' => $res_all,
	]);
}