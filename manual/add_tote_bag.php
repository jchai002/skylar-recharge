<?php
require_once(__DIR__.'/../includes/config.php');
require_once(__DIR__.'/../includes/class.ShopifyClient.php');
require_once(__DIR__.'/../includes/class.RechargeClient.php');

$rc = new RechargeClient();
$sc = new ShopifyPrivateClient();

$emails = explode(',','');

foreach($emails as $index => $email){
	$res = $rc->get('/customers', [
		'email' => $email,
	]);
	if(empty($res['customers'])){
		print_r($res);
		echo "Couldn't find customer: ".$email;
		die;
	}
	$customer = $res['customers'][0];
	$main_sub = sc_get_main_subscription($db, $rc, [
		'status' => 'ACTIVE',
		'customer_id' => $customer['id'],
	]);
	if(empty($main_sub)){
		print_r($main_sub);
		echo "Couldn't find sub: ".$email;
		die;
	}
	$res = $rc->post('/addresses/'.$main_sub['address_id'].'/onetimes', [
		'next_charge_scheduled_at' => $main_sub['next_charge_scheduled_at'],
		'product_title' => 'FREE Skylar Tote Bag',
		'price' => '0',
		'quantity' => 1,
		'shopify_variant_id' => 19871338332247,
	]);
	print_r($res);
	if(empty($res['onetime'])){
		echo "Couldn't create onetime: ".$email;
		die();
	}
	sleep(1);
}