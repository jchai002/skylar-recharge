<?php

require_once('../includes/config.php');
require_once('../includes/class.ShopifyClient.php');
require_once('../includes/class.RechargeClient.php');

$rc = new RechargeClient();
$sc = new ShopifyPrivateClient();

$rc_customer_ids = [14820280,14822974,14824390,14824747,14825911,14827699,14827975,14859163,14860054,14865220,14865556,14868133,14868223];

foreach($rc_customer_ids as $rc_customer_id){
	echo "Starting $rc_customer_id" . PHP_EOL;
	$sub_res = $rc->get('/subscriptions', ['limit' => 250, 'customer_id'=>$rc_customer_id]);

	$addresses_needing_discount = [];
	foreach($sub_res['subscriptions'] as $subscription){
		// Check for rollies
		foreach($ids_by_scent as $ids){
			if($subscription['shopify_product_id'] == $ids['product'] && $subscription['shopify_variant_id'] != $ids['variant']){
				echo "Rollie Fix" . PHP_EOL;
				$rc->put('/subscriptions/' . $subscription['id'], [
					'shopify_variant_id' => $ids['variant'],
					'variant_title' => 'Full Size (1.7 oz)',
					'price' => 78,
				]);
				break;
			}
		}
	}
}
die();

$page = 1;
do {
	echo "Starting page $page" . PHP_EOL;
	$sub_res = $rc->get('/subscriptions', ['limit' => 250, 'page' => $page, 'updated_at_min'=>'2018-09-05T00:00:00']); // status? ACTIVE or ONETIME

	$addresses_needing_discount = [];
	foreach($sub_res['subscriptions'] as $subscription){
		// Check for rollies
		foreach($ids_by_scent as $ids){
			if($subscription['shopify_product_id'] == $ids['product'] && $subscription['shopify_variant_id'] != $ids['variant']){
				echo "Rollie Fix".PHP_EOL;
				$rc->put('/subscriptions/'.$subscription['id'], [
					'shopify_variant_id' => $ids['variant'],
					'variant_title' => 'Full Size (1.7 oz)',
					'price' => 78,
				]);
				break;
			}
		}

		/*
		// Check for old sub type
		if(in_array($subscription['shopify_product_id'], [738567323735, 738567520343, 738394865751]) && $subscription['status'] == 'ACTIVE'){
			echo "Old product ID found!".PHP_EOL;
			var_dump($subscription);
			die();
		}
		*/


	}

	$page++;
	sleep(5);
} while(count($sub_res['subscriptions']) >= 250);
