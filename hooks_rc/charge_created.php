<?php
require_once(__DIR__.'/../includes/config.php');

$rc = new RechargeClient();
// get $charge from webhook
if(!empty($_REQUEST['id'])){
	$res = $rc->get('/charges/'.$_REQUEST['id']);
} else {
	$data = file_get_contents('php://input');
	if(!empty($data)){
		$res = json_decode($data, true);
	}
	log_event($db, 'webhook', $res['charge']['id'], 'charge_paid', $data);
}
var_dump($res);
if(empty($res['charge'])){
	echo "no charge";
	exit;
}
$charge = $res['charge'];

// Check if customer already has a subscription
$main_sub = sc_get_main_subscription($db, $rc, [
	'status' => 'ACTIVE',
	'customer_id' => $charge['customer_id'],
]);
//var_dump($main_sub);
$day_of_month = empty($main_sub['order_day_of_month']) ? '01' : $main_sub['order_day_of_month'];
if(empty($main_sub)){
	echo "no main sub".PHP_EOL;
	foreach($charge['line_items'] as $line_item){
		$product = get_product($db, $line_item['shopify_product_id']);
		$next_charge_date = date('Y-m', strtotime('+1 month')).'-'.$day_of_month.' 00:00:00';
//		var_dump($product);
		if(is_scent_club($product)){
			echo "scent club product".PHP_EOL;
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
			echo "Next Charge Date: ".sc_calculate_next_charge_date($db, $rc, $charge['address_id']).PHP_EOL;


			$profile_data = [];
			$props = [];
			foreach($line_item['properties'] as $property){
				$profile_data[str_replace('_sc_preference_', '', $property['name'])] = $property['value'];
				$props['$'.str_replace('_sc_preference_', '', $property['name'])] = $property['value'];
			}
			if(!empty($profile_data)){
				$stmt = $db->prepare("INSERT INTO sc_profile_data (shopify_customer_id, data_key, data_value) VALUES (:shopify_customer_id, :data_key, :data_value) ON DUPLICATE KEY UPDATE data_value=:data_value");
				if(empty($shopify_customer_id)){
					$customer_res = $rc->get('/customers/'.$charge['customer_id']);
					$shopify_customer_id = $customer_res['shopify_customer_id'];
				}
				foreach($profile_data as $key=>$value){
					$stmt->execute([
						'shopify_customer_id' => $shopify_customer_id,
						'data_key' => $key,
						'data_value' => $value,
					]);
				}
			}

			$props['email'] = $charge['email'];
			$props['$source'] = 'checkout';

			$ch = curl_init('https://a.klaviyo.com/api/v2/list/KLHcef/subscribe');
			curl_setopt_array($ch, [
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_POST => true,
				CURLOPT_POSTFIELDS => json_encode([
					'api_key' => $_ENV['KLAVIYO_API_KEY'],
					'profiles' => [
						$props,
					],
				]),
				CURLOPT_HTTPHEADER => [
					'api-key: '.$_ENV['KLAVIYO_API_KEY'],
					'Content-Type: application/json',
				],
			]);
			$res = curl_exec($ch);
			echo $res;
			log_event($db, 'klaviyo', $res, 'subscribe', $charge);
			break;
		}
	}
}

update_charge_discounts($db, $rc, [$charge]);
