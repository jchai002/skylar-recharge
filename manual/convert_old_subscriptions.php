<?php
require_once(__DIR__.'/../includes/config.php');

$convert_variant_ids = [
	12235409129559 => 30258959482967,
	12235492425815 => 30258951389271,
	12235492360279 => 30258952175703,
	12235492327511 => 30258950996055,
	12235492393047 => 30258958958679,
	12588614484055 => 30258961973335,
];

foreach($convert_variant_ids as $old_variant_id => $new_variant_id){
	// Load all active subscriptions
	$subscriptions = [];
	$onetimes = [];
	$page_size = 250;
	$page = 0;
	do {
		$page++;
		$res = $rc->get('/subscriptions', [
			'status' => 'ACTIVE',
			'limit' => $page_size,
			'page' => $page,
			'shopify_variant_id' => $old_variant_id,
		]);
		foreach($res['subscriptions'] as $subscription){
			// Sort them by address id
			$subscriptions[] = $subscription;
		}
	} while(count($res) >= $page_size);

	foreach($subscriptions as $subscription){
		echo "Updating ".$subscription['id']." from ".$subscription['shopify_variant_id']." to ".$new_variant_id.PHP_EOL;
		$res = $rc->put('/subscriptions/'.$subscription['id'], [
			'shopify_variant_id' => $new_variant_id,
		]);
		if(empty($res['subscription'])){
			echo "Error ".print_r($res);
			die();
		}
		insert_update_rc_subscription($db, $res['subscription'], $rc, $sc);
	}

	$onetimes = [];
	$page = 0;
	do {
		$page++;
		$res = $rc->get('/onetimes', [
			'limit' => $page_size,
			'page' => $page,
			'shopify_variant_id' => $old_variant_id,
		]);
		foreach($res['onetimes'] as $onetime){
			// Sort them by address id
			$onetimes[] = $onetime;
		}
	} while(count($res) >= $page_size);

	foreach($onetimes as $onetime){
		echo "Updating ".$onetime['id']." from ".$onetime['shopify_variant_id']." to ".$new_variant_id.PHP_EOL;
		$res = $rc->put('/onetimes/'.$onetime['id'], [
			'shopify_variant_id' => $new_variant_id,
		]);
		if(empty($res['onetime'])){
			echo "Error ".print_r($res);
			die();
		}
		insert_update_rc_subscription($db, $res['onetime'], $rc, $sc);
	}
}