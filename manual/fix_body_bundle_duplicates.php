<?php
require_once(__DIR__.'/../includes/config.php');

// Load all active subscriptions
$subscriptions_by_address = [];
$page_size = 250;
$page = 0;
do {
	$page++;
	$res = $rc->get('/subscriptions', [
		'status' => 'ACTIVE',
		'limit' => $page_size,
		'page' => $page,
		'shopify_variant_id' => 29450196680791,
	]);
	foreach($res['subscriptions'] as $subscription){
		// Sort them by address id
		if(empty($subscriptions_by_address[$subscription['address_id']])){
			$subscriptions_by_address[$subscription['address_id']] = [];
		}
		$subscriptions_by_address[$subscription['address_id']][] = $subscription;
//		echo insert_update_rc_subscription($db, $subscription, $rc, $sc).PHP_EOL;
	}
} while(count($res) >= $page_size);

// Iterate through all addresses, checking for double body bundles
foreach($subscriptions_by_address as $address_id => $subscriptions){
	$body_bundle_subs = [];
	foreach($subscriptions as $subscription){
		if(!get_product($db, $subscription['shopify_product_id'])['type'] == 'Body Bundle'){
			continue;
		}
		if(empty($body_bundle_subs[$subscription['shopify_variant_id']])){
			$body_bundle_subs[$subscription['shopify_variant_id']] = [];
		}
		$body_bundle_subs[$subscription['shopify_variant_id']][] = $subscription;
	}
	// If there's a double, delete the newer one
	foreach($body_bundle_subs as $variant_id => $variant_body_subs){
		if(count($variant_body_subs) < 2){
			continue;
		}
		usort($variant_body_subs, function($a, $b){
			if(strtotime($a['created_at']) == strtotime($b['created_at'])){
				return 0;
			}
			return strtotime($a['created_at']) < strtotime($b['created_at']) ? -1 : 1;
		});
		$keep_sub = array_shift($variant_body_subs);
		echo "Checking address ".$keep_sub['address_id'].PHP_EOL;
		echo "Keeping ".$keep_sub['id']." ".$keep_sub['created_at'].PHP_EOL;
		foreach($variant_body_subs as $subscription){
			echo "Deleting ".$subscription['id']." ".$subscription['created_at'].PHP_EOL;
			$res = $rc->post('/subscriptions/'.$subscription['id'].'/cancel', [
				'cancellation_reason' => 'Auto-cancelled - Bugfix',
				'send_email' => false,
			]);
		}
	}
}