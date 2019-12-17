<?php
require_once(__DIR__.'/../includes/config.php');

//$rc->debug = true;

$page = 0;
$scent = null;

$start_date = date('Y-m-t');
$end_date = date('Y-m-01', get_month_by_offset(2));

echo "Getting $start_date to $end_date".PHP_EOL;

$charges = [];

$start_time = microtime(true);
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
echo "Total: ".count($charges).PHP_EOL;

$starting_point = 0;
$num_to_process = count($charges);
if(!empty($argv) && !empty($argv[1])){
	$multi_info = explode('/', $argv[1]);
	$multi_info[0]--;
	$num_to_process = ceil(count($charges)/$multi_info[1]);
	$starting_point = floor($num_to_process * $multi_info[0]);
}

$start_time = microtime(true);
echo "Starting updates $starting_point - ".($starting_point+$num_to_process).PHP_EOL;
foreach($charges as $index=>$charge){
	if($index < $starting_point){
		continue;
	}
	if($index > $starting_point+$num_to_process){
		break;
	}
	echo "Swapping on address ".$charge['address_id']." ";
	$main_sub = sc_get_main_subscription($db, $rc, [
		'address_id' => $charge['address_id'],
		'status' => 'ACTIVE',
	]);
	$res = sc_swap_to_monthly_custom($db, $rc, $sc, $charge['address_id'], strtotime($charge['scheduled_at']), $main_sub);
	if($res == 'cancel'){
	    echo "Done.".PHP_EOL;
	    continue;
    }
	echo sc_calculate_next_charge_date($db, $rc, $charge['address_id'], $main_sub, 2).PHP_EOL;
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