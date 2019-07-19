<?php
require_once(__DIR__.'/../includes/config.php');

$rc = new RechargeClient();
$sc = new ShopifyClient();

$f = fopen(__DIR__.'/missing_sc.csv', 'r');

$headers = array_map('strtolower',fgetcsv($f));

$new_sku = '10213905-113';

$rownum = 0;
while($row_raw = fgetcsv($f)){
	$rownum++;
	$row = array_combine($headers, $row_raw);
	print_r($row);
	$row['charge_id'] = $row_raw[0];
	echo "Charge ID ".$row['charge_id'].PHP_EOL;
	$res = $rc->get('/charges/'.$row['charge_id']);
	if(empty($res['charge'])){
		print_r($res);
		die('Couldnt get charge');
	}
	$charge = $res['charge'];
	$main_sub = sc_get_main_subscription($db, $rc, [
		'status' => 'ACTIVE',
		'customer_id' => $charge['customer_id'],
	]);
	$day_of_month = empty($main_sub['order_day_of_month']) ? '01' : $main_sub['order_day_of_month'];
	if(!empty($main_sub)){
		continue;
	}

	echo "no main sub".PHP_EOL;
	foreach($charge['line_items'] as $line_item){
		$product = get_product($db, $line_item['shopify_product_id']);
		$next_charge_date = date('Y-m', strtotime('+1 month')) . '-' . $day_of_month . ' 00:00:00';
//		var_dump($product);
		if(is_scent_club($product)){
			echo "scent club product" . PHP_EOL;
			$res = $rc->post('/subscriptions', [
				'address_id' => $charge['address_id'],
				'next_charge_scheduled_at' => $next_charge_date,
				'product_title' => 'Skylar Scent Club',
				'price' => $line_item['price'],
				'quantity' => 1,
				'shopify_variant_id' => $line_item['shopify_variant_id'],
				'order_interval_unit' => 'month',
				'order_interval_frequency' => '1',
				'charge_interval_frequency' => '1',
				'order_day_of_month' => '1',
			]);
//			var_dump($res);
			if(!empty($res['subscription'])){
				$main_sub = $res['subscription'];
			}
			sleep(5);
			echo "Next Charge Date: " . sc_calculate_next_charge_date($db, $rc, $charge['address_id']) . PHP_EOL;
			sleep(5);

			$profile_data = [];
			foreach($line_item['properties'] as $property){
				$profile_data[str_replace('_sc_preference_', '', $property['name'])] = $property['value'];
			}
			if(!empty($profile_data)){
				$stmt = $db->prepare("INSERT INTO sc_profile_data (shopify_customer_id, data_key, data_value) VALUES (:shopify_customer_id, :data_key, :data_value) ON DUPLICATE KEY UPDATE data_value=:data_value");
				if(empty($shopify_customer_id)){
					$customer_res = $rc->get('/customers/' . $charge['customer_id']);
					$shopify_customer_id = $customer_res['customer']['shopify_customer_id'];
				}
				foreach($profile_data as $key => $value){
					$stmt->execute([
						'shopify_customer_id' => $shopify_customer_id,
						'data_key' => $key,
						'data_value' => $value,
					]);
				}
			}
		}
	}
}