<?php
require_once(__DIR__ . '/../../includes/config.php');

$log = [
	'lines' => '',
	'error' => false,
];

$page = 0;
$scent = null;

// TODO: This code is essentially looking for what scent is shipping next, has to be a cleaner way to do this
// Offset code so that this can be run month-of in case of issues
$offset = 0;
if(time() <= offset_date_skip_weekend(strtotime(date('Y-m-01')))){
	$offset = -1;
}
log_echo($log, "offset: $offset");

$start_date = date('Y-m-t', get_month_by_offset($offset));
$end_date = date('Y-m-01', get_month_by_offset(2+$offset));
log_echo($log, "$start_date - $end_date");

$scent_info = sc_get_monthly_scent($db, get_month_by_offset(1+$offset));
if(empty($scent_info)){
	//send_alert($db, 7, 'Tried to release scent, but there was no active monthly scent for '.date('Y-m-d', get_month_by_offset($offset)));
	die("No Live Monthly Scent!");
}
if(date('Y-m-d') == $scent_info['ship_date']){
	die("Today is the ship date, don't make any changes!");
}

log_echo($log, "Scent: ".print_r($scent_info, true));
log_echo($log, "Getting $start_date to $end_date");

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
	log_echo($log, "Adding ".count($res['charges'])." to array - total: ".count($charges));
	log_echo($log, "Rate: ".(count($charges) / (microtime(true) - $start_time))." charges/s");
} while(count($res['charges']) == 250);
log_echo($log, "Total: ".count($charges));

$starting_point = 0;
$num_to_process = count($charges);
if(!empty($argv) && !empty($argv[1])){
	$multi_info = explode('/', $argv[1]);
	$multi_info[0]--;
	$num_to_process = ceil(count($charges)/$multi_info[1]);
	$starting_point = floor($num_to_process * $multi_info[0]);
}

$start_time = microtime(true);
log_echo($log, "Starting updates $starting_point - ".($starting_point+$num_to_process));
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
send_alert($db, 8,
	"Finished releasing SC scent".($log['error'] ? ' with errors' : ''),
	"SC Scent Release".($log['error'] ? ' ERROR' : ' Log'),
	'tim@skylar.com',
	['log' => $log]
);