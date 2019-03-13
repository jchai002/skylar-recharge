<?php
require_once('../includes/config.php');
require_once('../includes/class.RechargeClient.php');



$rc = new RechargeClient();
// get $charge from webhook
if(!empty($_REQUEST['id'])){
	$res = $rc->get('/charges/'.$_REQUEST['id']);
} else {
	$data = file_get_contents('php://input');
	log_event($db, 'webhook', $data, 'charge_created');
	if(!empty($data)){
		$res = json_decode($data, true);
	}
}
var_dump($res);
if(empty($res['charge'])){
	exit;
}
$charge = $res['charge'];



// Check if customer already has a subscription
$main_sub = sc_get_main_subscription($db, $rc, [
	'status' => 'ACTIVE',
	'customer_id' => $charge['customer_id'],
]);
var_dump($main_sub);
$day_of_month = empty($main_sub['order_day_of_month']) ? '01' : $main_sub['order_day_of_month'];
if(empty($main_sub)){
	foreach($charge['line_items'] as $line_item){
		$product = get_product($db, $line_item['shopify_product_id']);
		$next_charge_date = date('Y-m', strtotime('+1 month')).'-'.$day_of_month.' 00:00:00';
		var_dump($product);
		if(is_scent_club($product)){
			$next_charge_date_time = strtotime($next_charge_date);
			if(date('Y', $next_charge_date_time) == 2019 && date('m', $next_charge_date_time) <= 4){
				$next_charge_date = '2019-05-'.date('d', $next_charge_date_time).' 00:00:00';
			}
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
			var_dump($res);
			if(!empty($res['subscription'])){
				$main_sub = $res['subscription'];
			}

			$props = [];
			foreach($line_item['properties'] as $key=>$val){
				$props['$'.$key] = $val;
			}

			$props['email'] = $charge['email'];
			$props['$source'] = $charge['checkout'];

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
			log_event($db, 'klaviyo', $res, 'subscribe', $charge);
			break;
		}
	}
}

update_charge_discounts($db, $rc, [$charge]);