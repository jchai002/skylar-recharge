<?php
require_once(__DIR__.'/../includes/config.php');
require_once(__DIR__.'/../includes/class.ShopifyClient.php');
require_once(__DIR__.'/../includes/class.RechargeClient.php');

$rc = new RechargeClient();
$sc = new ShopifyPrivateClient();


$f = fopen(__DIR__.'/promos.csv', 'r');

$headers = fgetcsv($f);

$rownum = 0;
while($row = fgetcsv($f)){
	$rownum++;
	$row = array_combine($headers, $row);
	print_r($row);

	$res = $rc->get('/customers', [
		'email' => $row['email'],
	]);
	if(empty($res['customers'])){
		$res =  $rc->post('/customers', [
			'email' => $row['email'],
			'first_name' => $row['first_name'],
			'last_name' => $row['last_name'],
			'billing_first_name' => $row['first_name'],
			'billing_last_name' => $row['last_name'],
			'billing_address1' => $row['address1'],
			'billing_address2' => $row['address2'],
			'billing_zip' => $row['zip'],
			'billing_city' => $row['city'],
			'billing_company' => $row['company'],
			'billing_province' => $row['state'],
			'billing_country' => $row['country'],
		]);
		if(empty($res['customer'])){
			echo "CREATE CUSTOMER ERROR:";
			print_r($row);
			print_r($res);
			sleep(30);
			continue;
		}
		$customer = $res['customer'];
	} else {
		$customer = $res['customers'][0];
	}

	$res = $rc->get('/subscriptions', [
		'customer_id' => $customer['id'],
	]);
	$address_id = false;
	foreach($res['subscriptions'] as $subscription){
		if($subscription['shopify_variant_id'] == 28003712663639){
			if($subscription['charge_interval_frequency'] == 11){
				continue 2;
			}
			$rc->delete('/subscriptions/'.$subscription['id']);
			$address_id = $subscription['address_id'];
		}
	}
	if(empty($address_id)){
		echo "find address ERROR:";
		print_r($res);
		sleep(30);
		continue;
	}

	if(empty($customer['stripe_customer_token'])){
		$res = $rc->put('/customers/'.$customer['id'], ['stripe_customer_token' => 'cus_EvyLMQQsXVkJTl','processor_type' => 'stripe',]);
		print_r($res);
	}
	$res = $rc->post('/addresses/'.$address_id.'/subscriptions', [
		'next_charge_scheduled_at' => '2019-04-23',
		'product_title' => 'Scent Club Promo',
		'price' => 0,
		'quantity' => 1,
		'shopify_variant_id' => 28003712663639,
		'order_interval_unit' => 'month',
		'order_interval_frequency' => 1,
		'charge_interval_frequency' => 11,
		'order_day_of_month' => 22,
		'expire_after_specific_number_of_charges' => 1,
	]);

	print_r($res);
	if($rownum >= 1){
//		die();
	}
}