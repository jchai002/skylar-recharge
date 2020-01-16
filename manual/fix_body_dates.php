<?php
require_once(__DIR__.'/../includes/config.php');

$variants = $db->query("
SELECT v.shopify_id FROM variants v
LEFT JOIN products p ON p.id=v.product_id
WHERE p.type IN ('Body Bundle', 'Body care');
")->fetchAll(PDO::FETCH_COLUMN);

foreach($variants as $variant_id){
	echo "Checking variant id ".$variant_id.PHP_EOL;
	$res = $rc->get('/subscriptions', [
		'status' => 'ACTIVE',
		'shopify_variant_id' => $variant_id,
	]);
	foreach($res['subscriptions'] as $subscription){
//		echo "Checking subscription ".$subscription['id'].PHP_EOL;
		$main_sub = sc_get_main_subscription($db, $rc, [
			'status' => 'ACTIVE',
			'address_id' => $subscription['address_id'],
		]);
		if(empty($main_sub)){
//			echo "Skipping, no main sub".PHP_EOL;
			continue;
		}
		if($subscription['order_day_of_month'] != null && $main_sub['order_day_of_month'] == $subscription['order_day_of_month']){
//			echo "Skipping, both have day of month ".$subscription['order_day_of_month'].PHP_EOL;
			continue;
		}
		if(date('d', strtotime($main_sub['next_charge_scheduled_at'])) == date('d', strtotime($subscription['next_charge_scheduled_at']))){
//			echo "Skipping, matching next charge date ".$subscription['order_day_of_month'].PHP_EOL;
			continue;
		}
		$order_day_of_month = $main_sub['order_day_of_month'] ?? date('d', strtotime($main_sub['next_charge_scheduled_at']));
		echo "Update address id ".$subscription['address_id']." to ".$order_day_of_month.PHP_EOL;
		$this_res = $rc->put('/subscriptions/'.$subscription['id'], [
			'order_day_of_month' => $order_day_of_month,
		]);
		if(!empty($this_res['errors'])){
			print_r($this_res['errors']);
			sleep(30);
			continue;
		}
		if($this_res['subscription']['order_interval_frequency'] == 1 && date('m', strtotime($this_res['subscription']['next_charge_scheduled_at'])) != '10'){
			// Recharge bug, moved ahead
			$this_res = $rc->post('/subscriptions/'.$subscription['id'].'/set_next_charge_date', ['date' => '2019-10-'.$order_day_of_month]);
		} else if($this_res['subscription']['order_interval_frequency'] == 2 && date('m', strtotime($this_res['subscription']['next_charge_scheduled_at'])) != '11'){
			$this_res = $rc->post('/subscriptions/'.$subscription['id'].'/set_next_charge_date', ['date' => '2019-11-'.$order_day_of_month]);
		}
		if(!empty($this_res['errors'])){
			print_r($this_res['errors']);
			sleep(30);
			continue;
		}
		echo insert_update_rc_subscription($db, $this_res['subscription'], $rc, $sc).PHP_EOL;
	}
}