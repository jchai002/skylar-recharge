<?php
require_once('../includes/config.php');
require_once('../includes/class.ShopifyClient.php');
require_once('../includes/class.RechargeClient.php');

$rc = new RechargeClient();
$sc = new ShopifyPrivateClient();


$f = fopen('charges.csv', 'r');

$headers = fgetcsv($f);


while($row = fgetcsv($f)){
	$row = array_combine($headers, $row);
	if(in_array($row['shopify product id'], [738567323735, 738567520343, 738394865751])){
		echo "Upgrade via curl, customer ID: ".$row['recharge customer id'].PHP_EOL;
		$ch = curl_init('https://ec2staging.skylar.com/account/get_subscriptions.php?rc_customer_id='.$row['recharge customer id']);
		curl_setopt_array($ch, [
			CURLOPT_RETURNTRANSFER => true,
		]);
		curl_exec($ch);
		sleep(2);
	}

	foreach($ids_by_scent as $ids){
		if($row['shopify product id'] == $ids['product'] && $row['shopify variant id'] != $ids['variant']){
			echo "Rollie Fix, customer ID: ".$row['recharge customer id'].PHP_EOL;
			$res = $rc->put('/subscriptions/'.$row['subscription id'], [
				'shopify_variant_id' => $ids['variant'],
				'variant_title' => 'Full Size (1.7 oz)',
				'price' => 78,
			]);
			sleep(1);
			break;
		}
	}
}