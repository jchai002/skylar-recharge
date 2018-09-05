<?php

require_once('../includes/config.php');
require_once('../includes/class.ShopifyClient.php');
require_once('../includes/class.RechargeClient.php');

$rc = new RechargeClient();
$sc = new ShopifyPrivateClient();

$page = 1;
do {
	echo "Starting page $page" . PHP_EOL;
	$sub_res = $rc->get('/subscriptions', ['limit' => 250, 'page' => $page, 'created_at_max' => '2018-09-05']);

	foreach($sub_res['subscriptions'] as $subscription){
		// Check for rollies
		foreach($ids_by_scent as $ids){
			if($subscription['shopify_product_id'] == $ids['product'] && $subscription['shopify_variant_id'] == $ids['variant']){
				echo "Rollie Fix".PHP_EOL;
				$rc->put('/subscriptions/'.$subscription['id'], [
					'shopify_variant_id' => $ids['variant'],
					'variant_title' => 'Full Size (1.7 oz)'
				]);
				break;
			}
		}

		// Check for old sub type
		if(in_array($subscription['shopify_product_id'], [738567323735, 738567520343, 738394865751]) && $subscription['status'] == 'ACTIVE'){
			echo "Old product ID found!".PHP_EOL;
			var_dump($subscription);
			die();
		}

	}

	$page++;
	sleep(5);
} while(count($sub_res['subscriptions']) >= 250);

?>