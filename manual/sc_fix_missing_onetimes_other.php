<?php
require_once(__DIR__.'/../includes/config.php');

$date = '2020-01-02';
$charges = [];
$page = 0;
$start_date = '2020-02-03';
$end_date = '2020-03-01';
$start_time = microtime(true);
$scent_info = sc_get_monthly_scent($db, get_next_month());
do {
	$page++;
	// Load month's upcoming queued charges
	$res = $rc->get('/charges', [
		'date_min' => $start_date,
		'date_max' => $end_date,
		'status' => 'QUEUED',
		'limit' => 250,
		'page' => $page,
//        'address_id' => '29102064',
	]);
	if(empty($res['charges'])){
		print_r($res);
		sleep(5);
		$page--;
	}
	foreach($res['charges'] as $charge){
		foreach($charge['line_items'] as $line_item){
			if(is_scent_club(get_product($db, $line_item['shopify_product_id']))){
				$charges[] = $charge;
				break;
			}
		}
	}
	echo "Adding ".count($res['charges'])." to array - total: ".count($charges).PHP_EOL;
	echo "Rate: ".(count($charges) / (microtime(true) - $start_time))." charges/s".PHP_EOL;
} while(count($res['charges']) == 250);

$starting_point = 0;
$num_to_process = count($charges);
$start_time = microtime(true);
echo "Starting updates $starting_point - ".($starting_point+$num_to_process).PHP_EOL;
foreach($charges as $index=>$charge){
	$address_id = $charge['address_id'];
	$charge_date = date('2020-01-d', strtotime($charge['scheduled_at']));
	$res = $rc->get('/charges', [
		'address_id'=>$address_id,
		'status' => 'QUEUED',
		'date_min' => '2019-12-31',
		'date_max' => '2019-02-01',
	]);
	foreach($res['charges'] as $address_charge){
		foreach($address_charge['line_items'] as $line_item){
			if($line_item['sku'] == $scent_info['sku'] || $line_item['shopify_variant_id'] == $scent_info['shopify_variant_id']){
				echo "Found existing charge, skipping".PHP_EOL;
				continue 3;
			}
		}
	}
	echo "Creating onetime on address ".$address_id." on ".$charge_date.PHP_EOL;
	sc_swap_to_monthly_custom($db, $rc, $sc, $address_id, strtotime($charge_date));
	if($index > 0 && $index % 20 == 0){
		$num_processed = $index - $starting_point;
		$elapsed_time = microtime(true) - $start_time;
		$charges_per_sec = $num_processed / $elapsed_time;
		$charges_remaining = $num_to_process - $num_processed;
		$time = microtime(true) + ($charges_remaining / $charges_per_sec);
		echo "Updated: ".$num_processed."/".$num_to_process." Rate: ".$charges_per_sec." charges/s, Estimated finish: ".date('Y-m-d H:i:s',$time).PHP_EOL;
	}
}

function sc_swap_to_monthly_custom(PDO $db, RechargeClient $rc, ShopifyClient $sc, $address_id, $time, $main_sub = []){
	if(empty($main_sub)){
		$main_sub = sc_get_main_subscription($db, $rc, [
			'address_id' => $address_id,
			'status' => 'ACTIVE',
		]);
	}
	if(empty($main_sub)){
//		echo "No Main Sub";
		return false;
	}
//	sc_delete_month_onetime($db, $rc, $address_id, $time);
	// Look up monthly scent
	$scent_info = sc_get_monthly_scent($db, $time, is_admin_address($address_id));
	if(empty($scent_info)){
		sc_calculate_next_charge_date($db, $rc, $address_id, $main_sub);
//		echo "No monthly scent";
		return false;
	}
	$properties = $main_sub['properties'];
	$properties['_swap'] = $main_sub['id'];
	$res = $rc->post('/addresses/'.$address_id.'/onetimes', [
		'next_charge_scheduled_at' => date('Y-m-d H:i:s', $time),
		'shopify_variant_id' => $scent_info['shopify_variant_id'],
		'properties' => $properties,
		'price' => $main_sub['price'],
		'quantity' => 1,
		'product_title' => 'Skylar Scent Club',
		'variant_title' => $scent_info['variant_title'],
	]);
	if(!empty($res['errors'])){
		if(!empty($res['errors']['general']) && $res['errors']['general'] == 'Must remove/fix existing error charges first'){
			echo "Invalid card - canceling main sub... ";
			$res = $rc->post('/subscriptions/'.$main_sub['id'].'/cancel', [
				'cancellation_reason' => 'Auto-cancelled - Invalid Payment Method Not Fixed',
				'send_email' => true,
			]);
			return "cancel";
		}
	}
	//print_r($res);
	if(empty($res['onetime'])){
		print_r($res);
		sleep(5);
		return false;
	}
	insert_update_rc_subscription($db, $res['onetime'], $rc, $sc);
	return $res['onetime'];
}