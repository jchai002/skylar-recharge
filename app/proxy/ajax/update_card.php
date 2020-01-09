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
		$res_all[] = $res = $stripe_customer = \Stripe\Customer::retrieve($customer['stripe_customer_token']);
		$stripe_customer->source = $token;
		$stripe_customer->save();

		$shopify_customer = $sc->get('/admin/customers/' . $_REQUEST['c'] . '.json');
		$res_all[] = $res = $rc->post('/customers/', [
			'shopify_customer_id' => $_REQUEST['c'],
			'email' => $shopify_customer['email'],
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
			'stripe_customer_token' => $stripe_customer->id,
		]);
		if(!empty($res['error'])){
			echo json_encode([
				'success' => false,
				'error' => $res['error'],
				'res' => $res_all,
			]);
		} else {
			echo json_encode([
				'success' => true,
				'res' => $res_all
			]);
		}
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