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
			'billing_address1' => trim($row['address1']),
			'billing_address2' => trim($row['address2']),
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

	$res = $rc->post('/customers/'.$customer['id'].'/addresses', [
		'first_name' => $row['first_name'],
		'last_name' => $row['last_name'],
		'address1' => $row['address1'],
		'address2' => $row['address2'],
		'zip' => $row['zip'],
		'city' => $row['city'],
		'company' => $row['company'],
		'province' => $row['state'],
		'country' => $row['country'],
		'phone' => '3869614848',
		'stripe_customer_token' => 'cus_EvyLMQQsXVkJTl',
		'processor_type' => 'stripe',
	]);

	$address = $res['address'];
	if(empty($res['address'])){
		echo "CREATE Address ERROR:";
		print_r($row);
		print_r($res);
		sleep(30);
		continue;
	}

	$res = $rc->put('/addresses/'.$address['id'], [
		'shipping_lines_override' => [
			[
				"code" => "Standard shipping",
				"price" => "0.00",
				"title" => "Standard shipping"
			]
		],
	]);
	print_r($res);

	$date = date('d') > 22 ? date('Y-m-22', strtotime('next month')) : date('Y-m-22');

	$res = $rc->post('/addresses/'.$address['id'].'/subscriptions', [
		'next_charge_scheduled_at' => $date,
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
	if(empty($res['address'])){
		echo "CREATE subscription ERROR:";
		print_r($row);
		print_r($res);
		sleep(30);
		continue;
	}

	print_r($res);
	if($rownum >= 1){
//		die();
	}
}